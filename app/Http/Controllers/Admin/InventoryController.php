<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionName;
use App\Enums\ProductType;
use App\Enums\RentalAssetCondition;
use App\Enums\RentalAssetStatus;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RentalAssetRequest;
use App\Http\Requests\Admin\StockAdjustmentRequest;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', RentalAsset::class);

        $tab = (string) $request->query('tab', 'assets');

        $assets = RentalAsset::query()
            ->with(['variant.product'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = $request->string('q')->toString();
                $query->where(fn ($inner) => $inner->where('asset_code', 'like', "%{$keyword}%")
                    ->orWhereHas('variant', fn ($variant) => $variant->where('name', 'like', "%{$keyword}%")->orWhere('sku', 'like', "%{$keyword}%")));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('asset_code')
            ->paginate(20, ['*'], 'assets_page')
            ->withQueryString();

        $saleVariants = ProductVariant::query()
            ->with('product')
            ->whereHas('product', fn ($query) => $query->whereIn('type', [ProductType::Sale->value, ProductType::Both->value]))
            ->orderBy('sku')
            ->paginate(20, ['*'], 'stock_page')
            ->withQueryString();

        $movements = StockMovement::query()
            ->with(['variant.product', 'rentalAsset', 'user'])
            ->latest('created_at')
            ->paginate(25, ['*'], 'movements_page');

        return view('admin.inventory.index', [
            'tab' => $tab,
            'assets' => $assets,
            'saleVariants' => $saleVariants,
            'movements' => $movements,
            'assetStatuses' => RentalAssetStatus::cases(),
            'movementTypes' => StockMovementType::cases(),
        ]);
    }

    public function createAsset(Request $request): View
    {
        $this->authorize('create', RentalAsset::class);

        return view('admin.inventory.asset-form', [
            'asset' => new RentalAsset(['status' => RentalAssetStatus::Available]),
            'variants' => $this->rentalVariants(),
            'statuses' => RentalAssetStatus::cases(),
            'conditions' => RentalAssetCondition::cases(),
        ]);
    }

    public function storeAsset(RentalAssetRequest $request): RedirectResponse
    {
        $this->authorize('create', RentalAsset::class);

        $variant = ProductVariant::query()
            ->whereHas('product', fn ($query) => $query->rental())
            ->findOrFail($request->integer('product_variant_id'));

        $this->inventory->createRentalAsset($variant, $request->validated(), $request->user());

        return redirect()->route('admin.inventory.index')->with('status', 'Unit rental ditambahkan.');
    }

    public function editAsset(RentalAsset $asset): View
    {
        $this->authorize('update', $asset);

        return view('admin.inventory.asset-form', [
            'asset' => $asset,
            'variants' => $this->rentalVariants(),
            'statuses' => RentalAssetStatus::cases(),
            'conditions' => RentalAssetCondition::cases(),
        ]);
    }

    public function updateAsset(RentalAssetRequest $request, RentalAsset $asset): RedirectResponse
    {
        $this->authorize('update', $asset);

        $this->inventory->updateRentalAsset($asset, $request->validated(), $request->user());

        return redirect()->route('admin.inventory.index')->with('status', 'Unit rental diperbarui.');
    }

    public function adjustStock(StockAdjustmentRequest $request, ProductVariant $variant): RedirectResponse
    {
        abort_unless(
            $request->user()->hasPermission(PermissionName::InventoryAdjustStock->value)
                || $request->user()->isOwner(),
            403,
        );

        $this->inventory->setSaleStock(
            $variant,
            $request->integer('quantity'),
            StockMovementType::from($request->string('type')->toString()),
            $request->string('reason')->toString(),
            $request->user(),
        );

        return redirect()->route('admin.inventory.index', ['tab' => 'stock'])->with('status', 'Stok jual disesuaikan.');
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    private function rentalVariants()
    {
        return ProductVariant::query()
            ->with('product')
            ->whereHas('product', fn ($query) => $query->rental())
            ->orderBy('sku')
            ->get();
    }
}
