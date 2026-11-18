<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RentalAssetStatus;
use App\Enums\RentalOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\RentalAssetAssignment;
use App\Models\RentalOrder;
use App\Services\InspectionService;
use App\Services\RentalHandoverService;
use App\Services\RentalOverdueService;
use App\Services\RentalReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use RuntimeException;

class OperationController extends Controller
{
    public function __construct(
        private readonly RentalHandoverService $handover,
        private readonly RentalReturnService $return,
        private readonly InspectionService $inspections,
        private readonly RentalOverdueService $overdue,
    ) {}

    public function pickups(Request $request): View
    {
        $this->gate('rentals.view');

        $orders = RentalOrder::query()
            ->with(['customer', 'payments', 'deposit', 'items.assignments.asset'])
            ->where('order_status', RentalOrderStatus::ReadyForPickup->value)
            ->latest()
            ->paginate(15);

        return view('admin.operations.pickups', [
            'orders' => $orders,
        ]);
    }

    public function handoverForm(Request $request, RentalOrder $order): View
    {
        $this->gate('rentals.handover');
        $order->load(['customer', 'items.assignments.asset', 'payments', 'deposit']);

        return view('admin.operations.handover', [
            'order' => $order,
            'overdue' => $this->overdue->forOrder($order),
        ]);
    }

    public function handover(Request $request, RentalOrder $order): RedirectResponse
    {
        $this->gate('rentals.handover');

        $data = $request->validate([
            'identity_checked' => ['required', 'boolean'],
            'identity_type' => ['nullable', 'string', 'max:32'],
            'customer_acknowledged' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $handover = $this->handover->handover($order, $data, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.operations.pickups')
            ->with('status', "Serah terima {$handover->handover_code} berhasil.");
    }

    public function returns(Request $request): View
    {
        $this->gate('rentals.view');

        $orders = RentalOrder::query()
            ->with(['customer', 'items.assignments.asset', 'handovers'])
            ->where('order_status', RentalOrderStatus::PickedUp->value)
            ->latest()
            ->paginate(15);

        return view('admin.operations.returns', [
            'orders' => $orders,
        ]);
    }

    public function returnForm(Request $request, RentalOrder $order): View
    {
        $this->gate('rentals.return');
        $order->load(['customer', 'items.assignments.asset', 'handovers', 'deposit']);

        $assignments = $order->assignments()
            ->whereNotNull('handed_over_at')
            ->whereNull('returned_at')
            ->with('asset')
            ->get();

        return view('admin.operations.return-form', [
            'order' => $order,
            'assignments' => $assignments,
        ]);
    }

    public function receive(Request $request, RentalOrder $order): RedirectResponse
    {
        $this->gate('rentals.return');

        $data = $request->validate([
            'assignment_ids' => ['nullable', 'array'],
            'assignment_ids.*' => ['integer', 'exists:rental_asset_assignments,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'customer_acknowledged' => ['nullable', 'boolean'],
        ]);

        try {
            $return = $this->return->receive($order, $data['assignment_ids'] ?? [], [
                'notes' => $data['notes'] ?? null,
                'customer_acknowledged' => (bool) ($data['customer_acknowledged'] ?? false),
            ], $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.operations.inspections')
            ->with('status', "Pengembalian {$return->return_code} dicatat.");
    }

    public function inspections(Request $request): View
    {
        $this->gate('inspections.view');

        $assignments = RentalAssetAssignment::query()
            ->whereHas('asset', fn ($q) => $q->where('status', RentalAssetStatus::InspectionPending->value))
            ->with(['asset', 'order.customer', 'returnItem', 'currentInspection'])
            ->latest()
            ->paginate(15);

        return view('admin.operations.inspections', [
            'assignments' => $assignments,
        ]);
    }

    public function finalizeInspection(Request $request, RentalAssetAssignment $assignment): RedirectResponse
    {
        $this->gate('inspections.create');

        $data = $request->validate([
            'result' => ['required', 'in:pass,fail'],
            'condition' => ['nullable', 'string', 'max:32'],
            'completeness_status' => ['nullable', 'string', 'max:32'],
            'damage_description' => ['nullable', 'string', 'max:500'],
            'missing_items' => ['nullable', 'array'],
            'outcome_status' => ['nullable', 'in:maintenance,damaged,lost'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $inspection = $this->inspections->finalizeReturn($assignment, $data, $request->user());

            // Coba selesaikan order jika semua unit sudah dilepas.
            $this->return->tryComplete($assignment->order, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Inspeksi selesai.');
    }

    public function overdue(Request $request): View
    {
        $this->gate('rentals.view');

        $now = now(config('app.timezone'));

        $orders = RentalOrder::query()
            ->with(['customer', 'items'])
            ->where('order_status', RentalOrderStatus::PickedUp->value)
            ->where('blocking_end_date', '<', $now->toDateString())
            ->latest()
            ->get()
            ->filter(fn ($order) => $this->overdue->isOverdue($order, $now))
            ->values();

        $page = (int) $request->query('page', 1);
        $perPage = 15;

        $paginated = new LengthAwarePaginator(
            $orders->forPage($page, $perPage)->values(),
            $orders->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('admin.operations.overdue', [
            'orders' => $paginated,
        ]);
    }

    private function gate(string $permission): void
    {
        if (! auth()->user()->hasPermission($permission)) {
            abort(403);
        }
    }
}
