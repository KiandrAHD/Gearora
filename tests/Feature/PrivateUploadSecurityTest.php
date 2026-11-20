<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\RoleName;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\RentalOrder;
use App\Models\User;
use App\Services\RentalOrderService;
use App\Support\RentalPeriod;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PHASE 8 — keamanan unggahan privat & endpoint media publik.
 */
class PrivateUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_valid_proof_is_stored_on_private_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $customer = $this->customer();
        $order = $this->rentalOrder($customer);

        $this->actingAs($customer)
            ->post(route('customer.orders.payment-proof.store', $order), [
                'proof' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertRedirect();

        $payment = $order->payments()->where('method', PaymentMethod::Transfer->value)->firstOrFail();
        $this->assertNotNull($payment->proof_path);
        Storage::disk('local')->assertExists($payment->proof_path);
        $this->assertStringContainsString('payment-proofs/'.$order->id, $payment->proof_path);

        // Tidak ada file yang jatuh ke disk publik.
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_invalid_extension_is_rejected(): void
    {
        Storage::fake('local');

        $customer = $this->customer();
        $order = $this->rentalOrder($customer);

        $this->actingAs($customer)
            ->post(route('customer.orders.payment-proof.store', $order), [
                'proof' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
            ])
            ->assertSessionHasErrors('proof');

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_oversized_file_is_rejected(): void
    {
        Storage::fake('local');
        config()->set('gearora.payment.proof_max_kb', 1);

        $customer = $this->customer();
        $order = $this->rentalOrder($customer);

        $this->actingAs($customer)
            ->post(route('customer.orders.payment-proof.store', $order), [
                'proof' => UploadedFile::fake()->create('big.jpg', 20, 'image/jpeg'),
            ])
            ->assertSessionHasErrors('proof');
    }

    public function test_public_media_serves_images_but_blocks_dangerous_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/ok.png', 'binary-content');

        $this->get(route('media.show', ['path' => 'products/ok.png']))->assertOk();

        // Ekstensi berbahaya & SVG tidak diizinkan.
        $this->get(route('media.show', ['path' => 'products/evil.php']))->assertNotFound();
        $this->get(route('media.show', ['path' => 'products/icon.svg']))->assertNotFound();

        // File yang tidak ada.
        $this->get(route('media.show', ['path' => 'products/missing.png']))->assertNotFound();

        // Path traversal (ter-encode).
        $this->get('/media/..%2f..%2f.env')->assertNotFound();
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Customer);

        return $user;
    }

    private function rentalOrder(User $customer): RentalOrder
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $variant = ProductVariant::factory()->for($product)->create(['rental_price_per_day' => 50000]);
        RentalAsset::factory()->for($variant, 'variant')->create();

        return app(RentalOrderService::class)->create(
            $customer,
            [['variant_id' => $variant->id, 'quantity' => 1]],
            RentalPeriod::from(now()->addDays(3)->toDateString(), now()->addDays(5)->toDateString()),
            PaymentMethod::Transfer,
        );
    }
}
