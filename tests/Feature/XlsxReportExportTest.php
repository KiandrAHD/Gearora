<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

class XlsxReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_downloads_valid_xlsx(): void
    {
        $this->confirmedSales('Pelanggan XLSX', 175000);

        $response = $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.export', ['report' => 'sales', 'format' => 'xlsx']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('gearora-sales', (string) $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringStartsWith('PK', $content);

        $spreadsheet = $this->load($content);
        $sheet = $spreadsheet->getSheet(0);
        $this->assertSame('Laporan Penjualan Retail', $sheet->getTitle());
        $this->assertSame('Kode Order', (string) $sheet->getCell('A4')->getValue());
    }

    public function test_currency_column_is_numeric_cell(): void
    {
        $this->confirmedSales('Pelanggan Angka', 175000);

        $content = $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.export', ['report' => 'sales', 'format' => 'xlsx']))
            ->streamedContent();

        $sheet = $this->load($content)->getSheet(0);
        $totalColumn = $this->columnFor($sheet, 'Total');

        $cell = $sheet->getCell($totalColumn.'5');
        $this->assertSame(DataType::TYPE_NUMERIC, $cell->getDataType());
        $this->assertEquals(175000, $cell->getValue());
    }

    public function test_formula_injection_is_stored_as_text(): void
    {
        $this->confirmedSales('=SUM(A1:A9)', 120000);

        $content = $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.export', ['report' => 'sales', 'format' => 'xlsx']))
            ->streamedContent();

        $sheet = $this->load($content)->getSheet(0);
        $customerColumn = $this->columnFor($sheet, 'Pelanggan');
        $value = (string) $sheet->getCell($customerColumn.'5')->getValue();

        $this->assertSame("'=SUM(A1:A9)", $value);
    }

    public function test_summary_workbook_respects_role_sheets(): void
    {
        $user = $this->userWith(['reports.sales.view', 'reports.export']);

        $content = $this->actingAs($user)
            ->get(route('admin.reports.export', ['report' => 'summary', 'format' => 'xlsx']))
            ->streamedContent();

        $spreadsheet = $this->load($content);
        $titles = array_map(fn ($sheet) => $sheet->getTitle(), $spreadsheet->getAllSheets());

        $this->assertContains('Ringkasan', $titles);
        $this->assertContains('Penjualan Retail', $titles);
        $this->assertNotContains('Pembayaran', $titles);
        $this->assertNotContains('Deposit', $titles);
    }

    public function test_export_permission_required_for_xlsx(): void
    {
        $viewOnly = $this->userWith(['reports.sales.view']);

        $this->actingAs($viewOnly)
            ->get(route('admin.reports.export', ['report' => 'sales', 'format' => 'xlsx']))
            ->assertForbidden();
    }

    private function load(string $content): Spreadsheet
    {
        $path = tempnam(sys_get_temp_dir(), 'gearora-xlsx-');
        file_put_contents($path, $content);

        try {
            return IOFactory::load($path);
        } finally {
            @unlink($path);
        }
    }

    private function columnFor(Worksheet $sheet, string $header): string
    {
        for ($column = 'A'; $column !== 'AA'; $column++) {
            if ((string) $sheet->getCell($column.'4')->getValue() === $header) {
                return $column;
            }
        }

        $this->fail("Header {$header} tidak ditemukan.");
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        $role = Role::factory()->create(['name' => 'xlsx_'.uniqid(), 'label' => 'XLSX Reporter']);
        $role->permissions()->sync(Permission::query()->whereIn('name', $permissions)->pluck('id')->all());

        $user = User::factory()->create();
        $user->assignRole(RoleName::WarehouseStaff);
        $user->assignRole($role->name);

        return $user;
    }

    private function confirmedSales(string $customerName, int $total): SalesOrder
    {
        $customer = User::factory()->create(['name' => $customerName]);
        $customer->assignRole(RoleName::Customer);

        return SalesOrder::factory()->confirmed()->create([
            'customer_id' => $customer->id,
            'subtotal' => $total,
            'total' => $total,
            'confirmed_at' => now(),
        ]);
    }

    private function staff(RoleName $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
