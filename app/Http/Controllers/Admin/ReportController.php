<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdditionalChargeStatus;
use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RentalOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ReportExportService;
use App\Services\ReportService;
use App\Support\CsvExporter;
use App\Support\Navigation\AdminNavigation;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /** @var array<int, string> */
    private const TYPES = ['summary', 'rental', 'sales', 'payments', 'deposits', 'charges', 'inventory', 'operations'];

    /** @var array<int, string> */
    private const FORMATS = ['csv', 'pdf', 'xlsx'];

    public function __construct(
        private readonly ReportService $reports,
        private readonly ReportExportService $exports,
    ) {}

    public function index(ReportFilterRequest $request): View
    {
        $user = $request->user();
        $key = $this->resolveType((string) $request->query('report', 'summary'));

        $this->authorizeReportView($user, $key);

        $filters = $this->reports->filters($request->start(), $request->end(), $request->options());
        $allowedTypes = $this->allowedTypes($user);
        $allowedGroups = $this->allowedGroups($user);

        $current = $key === 'summary'
            ? ['metrics' => $this->reports->dashboard($filters, $allowedGroups), 'table' => []]
            : $this->reports->report($key, $filters);

        return view('admin.reports.index', [
            'filters' => $filters,
            'active' => $key,
            'types' => $allowedTypes,
            'typeLabels' => $this->typeLabels(),
            'dashboard' => $this->reports->dashboard($filters, $allowedGroups),
            'current' => $current,
            'methods' => PaymentMethod::cases(),
            'statuses' => $this->statusesFor($key),
            'transactionTypes' => $this->transactionTypes(),
            'products' => $key === 'inventory' ? Product::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'categories' => $key === 'inventory' ? Category::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'canExport' => $this->canExport($user),
        ]);
    }

    public function export(ReportFilterRequest $request, string $report, string $format = 'csv'): Response|StreamedResponse
    {
        $user = $request->user();
        $key = $report;
        $format = strtolower($format);

        abort_unless(in_array($key, self::TYPES, true), 404);
        abort_unless(in_array($format, self::FORMATS, true), 404);

        $this->authorizeReportView($user, $key);
        $this->authorizeExport($user);

        $filters = $this->reports->filters($request->start(), $request->end(), $request->options());

        if ($format === 'csv') {
            $filename = "gearora-{$key}-{$filters['start_date']}_{$filters['end_date']}.csv";

            return CsvExporter::stream($filename, $this->reports->csvHeaders($key), $this->reports->csvRows($key, $filters, $this->allowedGroups($user)));
        }

        $reportData = $key === 'summary'
            ? ['metrics' => $this->reports->dashboard($filters, $this->allowedGroups($user)), 'table' => []]
            : $this->reports->report($key, $filters, true);

        if ($format === 'pdf') {
            return $this->exports->pdf($key, $filters, $reportData, (string) $user?->name);
        }

        return $this->exports->xlsx($key, $filters, $reportData);
    }

    private function resolveType(string $key): string
    {
        return in_array($key, self::TYPES, true) ? $key : 'summary';
    }

    private function permissionFor(string $key): ?string
    {
        return AdminNavigation::reportPermissionsByName()[$key] ?? null;
    }

    private function authorizeReportView(?User $user, string $key): void
    {
        if ($user === null) {
            abort(403);
        }

        if ($user->isOwner()) {
            return;
        }

        if ($key === 'summary') {
            abort_unless($user->hasAnyPermission(AdminNavigation::reportPermissions()), 403);

            return;
        }

        $permission = $this->permissionFor($key);
        abort_unless($permission !== null && $user->hasPermission($permission), 403);
    }

    private function authorizeExport(?User $user): void
    {
        abort_unless($user !== null && ($user->isOwner() || $user->hasPermission('reports.export')), 403);
    }

    private function canExport(?User $user): bool
    {
        return $user !== null && ($user->isOwner() || $user->hasPermission('reports.export'));
    }

    /** @return array<int, string> */
    private function allowedTypes(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return array_values(array_filter(self::TYPES, function (string $type) use ($user): bool {
            if ($user->isOwner()) {
                return true;
            }

            if ($type === 'summary') {
                return $user->hasAnyPermission(AdminNavigation::reportPermissions());
            }

            $permission = $this->permissionFor($type);

            return $permission !== null && $user->hasPermission($permission);
        }));
    }

    /** @return array<int, string> */
    private function allowedGroups(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return array_values(array_filter(
            ['rental', 'sales', 'payments', 'deposits', 'charges', 'inventory', 'operations'],
            fn (string $group) => $user->isOwner() || $user->hasPermission($this->permissionFor($group) ?? ''),
        ));
    }

    /** @return array<string, string> */
    private function typeLabels(): array
    {
        return [
            'summary' => 'Ringkasan', 'rental' => 'Rental', 'sales' => 'Retail', 'payments' => 'Pembayaran',
            'deposits' => 'Deposit', 'charges' => 'Biaya Tambahan', 'inventory' => 'Inventaris', 'operations' => 'Operasional',
        ];
    }

    /** @return array<string, string> */
    private function statusesFor(string $key): array
    {
        $options = fn (array $cases) => collect($cases)->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();

        return match ($key) {
            'rental', 'operations' => $options(RentalOrderStatus::cases()),
            'sales' => $options(SalesOrderStatus::cases()),
            'payments' => $options(PaymentStatus::cases()),
            'deposits' => $options(DepositStatus::cases()),
            'charges' => $options(AdditionalChargeStatus::cases()),
            default => [],
        };
    }

    /** @return array<int, string> */
    private function transactionTypes(): array
    {
        return ['rental', 'sales'];
    }

    /** @return array<string, string> */
    private function transactionTypeOptions(): array
    {
        return ['rental' => 'Rental', 'sales' => 'Penjualan retail'];
    }
}
