<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepositStatus;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\DepositService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DepositController extends Controller
{
    public function __construct(private readonly DepositService $deposits) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Deposit::class);

        $deposits = Deposit::query()
            ->with(['order.customer'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = $request->string('q')->toString();
                $query->whereHas('order', fn ($order) => $order
                    ->where('order_code', 'like', "%{$keyword}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$keyword}%")));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.deposits.index', [
            'deposits' => $deposits,
            'statuses' => DepositStatus::cases(),
        ]);
    }

    public function show(Deposit $deposit): View
    {
        $this->authorize('view', $deposit);

        $deposit->load(['order.customer', 'transactions' => fn ($query) => $query->latest('id')]);

        return view('admin.deposits.show', [
            'deposit' => $deposit,
        ]);
    }

    public function refund(Request $request, Deposit $deposit): RedirectResponse
    {
        $this->authorize('refund', $deposit);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $applied = $this->deposits->refund($deposit, (int) $data['amount'], $data['reason'], $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Deposit dikembalikan Rp'.number_format($applied, 0, ',', '.').'.');
    }

    public function deduct(Request $request, Deposit $deposit): RedirectResponse
    {
        $this->authorize('deduct', $deposit);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $applied = $this->deposits->deduct($deposit, (int) $data['amount'], $data['reason'], $request->user());

        return back()->with('status', 'Deposit dipotong Rp'.number_format($applied, 0, ',', '.').'.');
    }

    public function forfeit(Request $request, Deposit $deposit): RedirectResponse
    {
        $this->authorize('manage', $deposit);

        $reason = $request->validate(['reason' => ['required', 'string', 'max:255']])['reason'];

        $this->deposits->forfeit($deposit, $reason, $request->user());

        return back()->with('status', 'Deposit ditandai hangus.');
    }
}
