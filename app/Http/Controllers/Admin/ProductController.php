<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ActivityLogger;
use App\Services\ProductLifecycleService;
use App\Services\ProductMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductMediaService $media,
        private readonly ProductLifecycleService $lifecycle,
        private readonly ActivityLogger $logger,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with([
                'category',
                'primaryImage',
                'variants' => fn ($query) => $query->withCount(['rentalAssets', 'stockMovements', 'orderItems']),
            ])
            ->withCount('orderItems')
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = $request->string('q')->toString();
                $query->where(fn ($inner) => $inner->where('name', 'like', "%{$keyword}%")
                    ->orWhere('brand', 'like', "%{$keyword}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $productDependencies = $products->getCollection()
            ->mapWithKeys(fn (Product $product) => [$product->id => $this->lifecycle->productDependencies($product)]);

        return view('admin.products.index', [
            'products' => $products,
            'productDependencies' => $productDependencies,
            'categories' => Category::query()->orderBy('name')->get(),
            'statuses' => ProductStatus::cases(),
            'types' => ProductType::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.form', [
            'product' => new Product(['type' => ProductType::Rental, 'status' => ProductStatus::Draft]),
            'categories' => Category::query()->orderBy('name')->get(),
            'types' => ProductType::cases(),
            'statuses' => ProductStatus::cases(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $product = Product::query()->create($this->payload($request));
        $this->logger->log('product.created', $product, "Produk {$product->name} dibuat.", ['name' => $product->name, 'type' => $product->type->value]);

        $this->storeUploadedImage($request, $product);

        return redirect()->route('admin.products.edit', $product)
            ->with('status', 'Produk dibuat. Tambahkan varian dan foto produk.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load([
            'variants' => fn ($query) => $query->withCount(['rentalAssets', 'stockMovements', 'orderItems']),
            'images',
        ]);

        $variantDependencies = $product->variants
            ->mapWithKeys(fn (ProductVariant $variant) => [$variant->id => $this->lifecycle->variantDependencies($variant)]);

        return view('admin.products.form', [
            'product' => $product,
            'variantDependencies' => $variantDependencies,
            'categories' => Category::query()->orderBy('name')->get(),
            'types' => ProductType::cases(),
            'statuses' => ProductStatus::cases(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->fill($this->payload($request))->save();
        $this->logger->log('product.updated', $product, "Produk {$product->name} diperbarui.");

        $this->storeUploadedImage($request, $product);

        $redirect = $request->input('return_to') === 'index'
            ? route('admin.products.index')
            : route('admin.products.edit', $product);

        return redirect($redirect)->with('status', 'Produk diperbarui.');
    }

    public function archive(Product $product): RedirectResponse
    {
        $this->authorize('archive', $product);

        $product->forceFill(['status' => ProductStatus::Archived])->save();
        $this->logger->log('product.archived', $product, "Produk {$product->name} diarsipkan.");

        return redirect()->route('admin.products.index')
            ->with('status', 'Produk diarsipkan dan tidak tampil di katalog publik.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        try {
            $this->lifecycle->deleteProduct($product);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.products.index')
                ->with('error', $exception->getMessage().' Gunakan Arsipkan untuk menyimpan riwayat.');
        }

        return redirect()->route('admin.products.index')
            ->with('status', "Produk {$product->name} dihapus permanen.");
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ProductRequest $request): array
    {
        $data = $request->validated();
        unset($data['specifications']);
        $data['specifications'] = $request->specifications();

        return $data;
    }

    private function storeUploadedImage(Request $request, Product $product): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        try {
            $this->media->store($product, $request->file('image'), $request->boolean('image_primary'), $product->name);
        } catch (RuntimeException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }
}
