<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Upload dan pengelolaan foto produk.
 *
 * - Validasi MIME/ukuran dilakukan di Form Request.
 * - Nama file selalu acak; path tidak pernah berasal dari input pengguna.
 * - File executable tidak diizinkan (hanya ekstensi gambar).
 */
class ProductMediaService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function store(Product $product, UploadedFile $file, bool $primary = false, ?string $alt = null): ProductImage
    {
        $this->assertAllowedImage($file);

        return DB::transaction(function () use ($product, $file, $primary, $alt) {
            $disk = config('gearora.uploads.public_disk');
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            $filename = Str::random(40).'.'.$extension;
            $path = $file->storeAs('products/'.$product->getKey(), $filename, ['disk' => $disk, 'visibility' => 'public']);

            $isPrimary = $primary || ! $product->images()->exists();

            if ($isPrimary) {
                $product->images()->update(['is_primary' => false]);
            }

            $image = $product->images()->create([
                'path' => $path,
                'alt' => $alt ?: $product->name,
                'is_primary' => $isPrimary,
                'sort_order' => (int) $product->images()->max('sort_order') + 1,
            ]);

            if ($isPrimary) {
                $product->forceFill(['thumbnail_path' => $path])->save();
            }

            $this->logger->log('product.image_uploaded', $product, "Foto produk {$product->name} diunggah.", ['image_id' => $image->getKey()]);

            return $image;
        });
    }

    public function setPrimary(Product $product, ProductImage $image): void
    {
        if ($image->product_id !== $product->getKey()) {
            throw new RuntimeException('Foto tidak termasuk produk ini.');
        }

        DB::transaction(function () use ($product, $image) {
            $product->images()->update(['is_primary' => false]);
            $image->forceFill(['is_primary' => true])->save();
            $product->forceFill(['thumbnail_path' => $image->path])->save();
        });

        $this->logger->log('product.image_primary_set', $product, "Foto utama produk {$product->name} diubah.", ['image_id' => $image->getKey()]);
    }

    public function delete(Product $product, ProductImage $image): void
    {
        if ($image->product_id !== $product->getKey()) {
            throw new RuntimeException('Foto tidak termasuk produk ini.');
        }

        DB::transaction(function () use ($product, $image) {
            $wasPrimary = $image->is_primary;
            $path = $image->path;

            $image->delete();
            Storage::disk(config('gearora.uploads.public_disk'))->delete($path);

            if ($wasPrimary) {
                $next = $product->images()->orderBy('sort_order')->first();

                $product->forceFill(['thumbnail_path' => $next?->path])->save();

                if ($next !== null) {
                    $next->forceFill(['is_primary' => true])->save();
                }
            }
        });

        $this->logger->log('product.image_deleted', $product, "Foto produk {$product->name} dihapus.", ['image_id' => $image->getKey()]);
    }

    private function assertAllowedImage(UploadedFile $file): void
    {
        $mime = (string) $file->getMimeType();
        $allowed = config('gearora.uploads.image_mimes');
        $extension = strtolower($file->getClientOriginalExtension() ?: '');

        if (! in_array($mime, $allowed, true) || ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new RuntimeException('Format gambar harus JPG, PNG, atau WEBP.');
        }

        if ($file->getSize() > config('gearora.uploads.image_max_kb') * 1024) {
            throw new RuntimeException('Ukuran gambar melebihi batas yang diizinkan.');
        }
    }
}
