<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Enums\SalesOrderStatus;
use App\Models\SalesOrder;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_can_download_sales_csv_with_headers(): void
    {
        $this->confirmedOrder();

        $response = $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.export', ['report' => 'sales']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=', (string) $response->headers->get('content-disposition'));

        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Kode Order', $content);
        $this->assertStringContainsString('Jumlah Item', $content);
        $this->assertStringContainsString('GRS-', $content);
    }

    public function test_export_blocks_formula_injection_values(): void
    {
        $this->confirmedOrder(customerName: '=SUM(A1:A9)');

        $content = $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.export', ['report' => 'sales']))
            ->streamedContent();

        // Nilai diberi prefiks apostrof sehingga Excel memperlakukannya sebagai teks.
        $this->assertStringContainsString("'=SUM(A1:A9)", $content);
        $this->assertStringNotContainsString(',=SUM(A1:A9)', $content);
    }

    public function test_summary_export_contains_metrics(): void
    {
        $content = $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.export', ['report' => 'summary']))
            ->streamedContent();

        $this->assertStringContainsString('Rental net revenue', $content);
        $this->assertStringContainsString('Retail net sales', $content);
    }

    public function test_unknown_report_export_returns_not_found(): void
    {
        $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.export', ['report' => 'unknown']))
            ->assertNotFound();
    }

    public function test_warehouse_cannot_export_reports(): void
    {
        $this->actingAs($this->staff(RoleName::WarehouseStaff))
            ->get(route('admin.reports.export', ['report' => 'sales']))
            ->assertForbidden();
    }

    public function test_customer_cannot_export_reports(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(RoleName::Customer);

        $this->actingAs($customer)
            ->get(route('admin.reports.export', ['report' => 'sales']))
            ->assertForbidden();
    }

    private function confirmedOrder(string $customerName = 'Pelanggan CSV'): SalesOrder
    {
        $customer = User::factory()->create(['name' => $customerName]);
        $customer->assignRole(RoleName::Customer);

        return SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'order_status' => SalesOrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Verified,
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
