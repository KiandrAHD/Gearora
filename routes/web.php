<?php

use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ChargeController as AdminChargeController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DepositController as AdminDepositController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\OperationController as AdminOperationController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductImageController as AdminProductImageController;
use App\Http\Controllers\Admin\ProductVariantController as AdminProductVariantController;
use App\Http\Controllers\Admin\RefundController as AdminRefundController;
use App\Http\Controllers\Admin\RentalOrderController as AdminRentalOrderController;
use App\Http\Controllers\Auth\CustomerLoginController;
use App\Http\Controllers\Auth\CustomerRegisterController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\MidtransCheckoutController;
use App\Http\Controllers\Customer\PaymentProofController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\RentalOrderController as CustomerRentalOrderController;
use App\Http\Controllers\PublicSite\AvailabilityController;
use App\Http\Controllers\PublicSite\BookingController;
use App\Http\Controllers\PublicSite\CatalogController;
use App\Http\Controllers\PublicSite\GuideController;
use App\Http\Controllers\PublicSite\HomeController;
use App\Http\Controllers\PublicSite\MediaController;
use App\Http\Controllers\PublicSite\ProductController;
use App\Http\Controllers\PublicSite\TripPlannerController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/katalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/katalog/{product:slug}', [ProductController::class, 'show'])->name('catalog.show');
Route::get('/panduan', [GuideController::class, 'index'])->name('guides.index');
Route::get('/rekomendasi-trip', [TripPlannerController::class, 'index'])->name('trip-planner.index');
Route::get('/media/{path}', [MediaController::class, 'show'])->where('path', '.*')->name('media.show');
Route::post('/ketersediaan', [AvailabilityController::class, 'check'])->name('availability.check');

Route::post('/pesanan/konfirmasi', [BookingController::class, 'store'])->name('booking.store');
Route::get('/pesanan/konfirmasi', [BookingController::class, 'show'])->name('booking.summary');
Route::delete('/pesanan/konfirmasi', [BookingController::class, 'destroy'])->name('booking.destroy');

Route::middleware('guest')->group(function (): void {
    Route::get('/masuk', [CustomerLoginController::class, 'create'])->name('login');
    Route::post('/masuk', [CustomerLoginController::class, 'store'])->name('login.store');
    Route::get('/daftar', [CustomerRegisterController::class, 'create'])->name('register');
    Route::post('/daftar', [CustomerRegisterController::class, 'store'])->name('register.store');
});
Route::post('/keluar', [CustomerLoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('akun')->middleware('auth')->as('customer.')->group(function (): void {
    Route::get('/', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/pesanan', [CustomerRentalOrderController::class, 'index'])->name('orders.index');
    Route::post('/pesanan', [CustomerRentalOrderController::class, 'store'])->name('orders.store');
    Route::get('/pesanan/{order}', [CustomerRentalOrderController::class, 'show'])->name('orders.show');
    Route::post('/pesanan/{order}/batal', [CustomerRentalOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/pesanan/{order}/bukti-pembayaran', [PaymentProofController::class, 'store'])->name('orders.payment-proof.store');
    Route::post('/pesanan/{order}/pembayaran-midtrans', [MidtransCheckoutController::class, 'start'])->name('orders.midtrans.start');
    Route::get('/pesanan/{order}/pembayaran-midtrans/selesai', [MidtransCheckoutController::class, 'finish'])->name('orders.midtrans.finish');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
});

Route::prefix('admin')->as('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'staff'])->group(function (): void {
        Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout');
        Route::get('/', [AdminDashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');

        Route::resource('categories', AdminCategoryController::class)->except('show');
        Route::resource('products', AdminProductController::class)->except('show', 'destroy');
        Route::post('/products/{product}/archive', [AdminProductController::class, 'archive'])->name('products.archive');
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/variants', [AdminProductVariantController::class, 'store'])->name('products.variants.store');
        Route::put('/products/{product}/variants/{variant}', [AdminProductVariantController::class, 'update'])->name('products.variants.update');
        Route::delete('/products/{product}/variants/{variant}', [AdminProductVariantController::class, 'destroy'])->name('products.variants.destroy');
        Route::post('/products/{product}/images', [AdminProductImageController::class, 'store'])->name('products.images.store');
        Route::post('/products/{product}/images/{image}/primary', [AdminProductImageController::class, 'primary'])->name('products.images.primary');
        Route::delete('/products/{product}/images/{image}', [AdminProductImageController::class, 'destroy'])->name('products.images.destroy');

        Route::get('/inventory', [AdminInventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/assets/create', [AdminInventoryController::class, 'createAsset'])->name('inventory.assets.create');
        Route::post('/inventory/assets', [AdminInventoryController::class, 'storeAsset'])->name('inventory.assets.store');
        Route::get('/inventory/assets/{asset}/edit', [AdminInventoryController::class, 'editAsset'])->name('inventory.assets.edit');
        Route::put('/inventory/assets/{asset}', [AdminInventoryController::class, 'updateAsset'])->name('inventory.assets.update');
        Route::post('/inventory/variants/{variant}/stock', [AdminInventoryController::class, 'adjustStock'])->name('inventory.stock.adjust');

        Route::get('/rentals', [AdminRentalOrderController::class, 'index'])->name('rentals.index');
        Route::get('/rentals/{order}', [AdminRentalOrderController::class, 'show'])->name('rentals.show');
        Route::post('/rentals/{order}/transition', [AdminRentalOrderController::class, 'transition'])->name('rentals.transition');

        Route::prefix('operations')->as('operations.')->group(function (): void {
            Route::get('/pickups', [AdminOperationController::class, 'pickups'])->name('pickups');
            Route::get('/pickups/{order}', [AdminOperationController::class, 'handoverForm'])->name('pickups.show');
            Route::post('/pickups/{order}/handover', [AdminOperationController::class, 'handover'])->name('pickups.handover');
            Route::get('/returns', [AdminOperationController::class, 'returns'])->name('returns');
            Route::get('/returns/{order}', [AdminOperationController::class, 'returnForm'])->name('returns.show');
            Route::post('/returns/{order}/receive', [AdminOperationController::class, 'receive'])->name('returns.receive');
            Route::get('/inspections', [AdminOperationController::class, 'inspections'])->name('inspections');
            Route::post('/inspections/{assignment}', [AdminOperationController::class, 'finalizeInspection'])->name('inspections.finalize');
            Route::get('/overdue', [AdminOperationController::class, 'overdue'])->name('overdue');
        });
        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/{payment}/decision', [AdminPaymentController::class, 'decide'])->name('payments.decide');
        Route::post('/payments/{payment}/cash', [AdminPaymentController::class, 'recordCash'])->name('payments.cash');
        Route::get('/payments/{payment}/proof', [AdminPaymentController::class, 'proof'])->name('payments.proof');

        Route::get('/refunds', [AdminRefundController::class, 'index'])->name('refunds.index');
        Route::post('/refunds', [AdminRefundController::class, 'store'])->name('refunds.store');
        Route::post('/refunds/{refund}/approve', [AdminRefundController::class, 'approve'])->name('refunds.approve');
        Route::post('/refunds/{refund}/reject', [AdminRefundController::class, 'reject'])->name('refunds.reject');
        Route::post('/refunds/{refund}/process', [AdminRefundController::class, 'process'])->name('refunds.process');
        Route::post('/refunds/{refund}/complete', [AdminRefundController::class, 'complete'])->name('refunds.complete');

        Route::get('/deposits', [AdminDepositController::class, 'index'])->name('deposits.index');
        Route::get('/deposits/{deposit}', [AdminDepositController::class, 'show'])->name('deposits.show');
        Route::post('/deposits/{deposit}/refund', [AdminDepositController::class, 'refund'])->name('deposits.refund');
        Route::post('/deposits/{deposit}/deduct', [AdminDepositController::class, 'deduct'])->name('deposits.deduct');
        Route::post('/deposits/{deposit}/forfeit', [AdminDepositController::class, 'forfeit'])->name('deposits.forfeit');

        Route::get('/charges', [AdminChargeController::class, 'index'])->name('charges.index');
        Route::post('/charges', [AdminChargeController::class, 'store'])->name('charges.store');
        Route::post('/charges/{charge}/approve', [AdminChargeController::class, 'approve'])->name('charges.approve');
        Route::post('/charges/{charge}/reject', [AdminChargeController::class, 'reject'])->name('charges.reject');
        Route::post('/charges/{charge}/pay', [AdminChargeController::class, 'pay'])->name('charges.pay');
        Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
        Route::get('/activity', [AdminActivityLogController::class, 'index'])->name('activity.index');
    });
});
