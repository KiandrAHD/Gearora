<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Enums\SalesOrderStatus;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\ReportExportService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PdfReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_downloads_pdf_with_branding_and_filename(): void
    {
        $this->confirmedSales('Pelanggan PDF', 150000);

        $response = $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.export', ['report' => 'sales', 'format' => 'pdf']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('gearora-sales', $disposition);
        $this->assertStringContainsString('.pdf', $disposition);

        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF', $content);
    }

    public function test_summary_pdf_renders_empty_state(): void
    {
        $response = $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.export', ['report' => 'summary', 'format' => 'pdf']));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_filters_do_not_break_pdf_export(): void
    {
        $this->confirmedSales('Pelanggan Filter', 150000);

        $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.export', [
                'report' => 'sales',
                'format' => 'pdf',
                'start_date' => '2000-01-01',
                'end_date' => '2000-01-31',
            ]))
            ->assertOk();
    }

    public function test_pdf_export_respects_row_limit(): void
    {
        $customer = User::factory()->create();
        $rows = [];
        $now = now();

        for ($i = 0; $i < ReportExportService::PDF_MAX_ROWS + 5; $i++) {
            $rows[] = [
                'order_code' => 'BULK-PDF-'.$i,
                'customer_id' => $customer->id,
                'subtotal' => 1000,
                'discount_total' => 0,
                'total' => 1000,
                'payment_status' => PaymentStatus::Verified->value,
                'order_status' => SalesOrderStatus::Confirmed->value,
                'confirmed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('sales_orders')->insert($rows);

        $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.export', ['report' => 'sales', 'format' => 'pdf']))
            ->assertStatus(422);
    }

    public function test_warehouse_cannot_export_pdf(): void
    {
        $this->actingAs($this->staff(RoleName::WarehouseStaff))
            ->get(route('admin.reports.export', ['report' => 'sales', 'format' => 'pdf']))
            ->assertForbidden();
    }

    public function test_unknown_format_returns_not_found(): void
    {
        $this->actingAs($this->staff(RoleName::Owner))
            ->get(route('admin.reports.export', ['report' => 'sales', 'format' => 'docx']))
            ->assertNotFound();
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
