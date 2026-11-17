<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ProductImageController extends Controller
{
    public function __construct(private readonly ProductMediaService $media) {}

    public function store(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.config('gearora.uploads.image_max_kb')],
            'alt' => ['nullable', 'string', 'max:180'],
        ], [
            'image.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
        ]);

        try {
            $this->media->store($product, $request->file('image'), $request->boolean('is_primary'), $request->string('alt')->toString() ?: null);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Foto produk diunggah.');
    }

    public function primary(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('update', $product);

        try {
            $this->media->setPrimary($product, $image);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Foto utama diperbarui.');
    }

    public function destroy(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('update', $product);

        try {
            $this->media->delete($product, $image);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Foto dihapus.');
    }
}
