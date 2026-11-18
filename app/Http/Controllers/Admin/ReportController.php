<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Services\ReportService;
use App\Support\CsvExporter;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /** @var array<int, string> */
    private const TYPES = ['summary', 'rental', 'sales', 'payments', 'deposits', 'charges', 'inventory', 'operations'];

    public function __construct(private readonly ReportService $reports) {}

    public function index(ReportFilterRequest $request): View
    {
        $this->authorizeView();

        $filters = $this->reports->filters($request->start(), $request->end(), [
            'status' => $request->validated('status'),
            'type' => $request->validated('type'),
            'method' => $request->validated('method'),
            'product_id' => $request->validated('product_id'),
            'category_id' => $request->validated('category_id'),
        ]);

        $key = (string) ($request->query('report') ?: 'summary');

        if (! in_array($key, self::TYPES, true)) {
            $key = 'summary';
        }

        return view('admin.reports.index', [
            'filters' => $filters,
            'active' => $key,
            'types' => self::TYPES,
            'dashboard' => $this->reports->dashboard($filters),
            'current' => $this->reports->report($key, $filters),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function export(ReportFilterRequest $request, string $report): StreamedResponse
    {
        $this->authorizeExport();

        if (! in_array($report, self::TYPES, true)) {
            abort(404);
        }

        $filters = $this->reports->filters($request->start(), $request->end(), [
            'status' => $request->validated('status'),
            'type' => $request->validated('type'),
            'method' => $request->validated('method'),
            'product_id' => $request->validated('product_id'),
            'category_id' => $request->validated('category_id'),
        ]);

        $filename = 'gearora-'.$report.'-'.$filters['start_date'].'_'.$filters['end_date'].'.csv';

        return CsvExporter::stream(
            $filename,
            $this->reports->csvHeaders($report),
            $this->reports->csvRows($report, $filters),
        );
    }

    private function authorizeView(): void
    {
        $user = request()->user();

        abort_unless(
            $user !== null && ($user->isOwner()
                || $user->hasPermission(PermissionName::ReportsRentalsView->value)
                || $user->hasPermission(PermissionName::ReportsSalesView->value)),
            403,
        );
    }

    private function authorizeExport(): void
    {
        $user = request()->user();

        abort_unless(
            $user !== null && ($user->isOwner() || $user->hasPermission(PermissionName::ReportsExport->value)),
            403,
        );
    }
}
