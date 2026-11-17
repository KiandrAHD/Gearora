<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan aset publik dari Laravel Storage tanpa bergantung pada symlink
 * `public/storage` (yang tidak selalu dapat dibuat pada shared hosting).
 */
class MediaController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'];

    public function show(Request $request, string $path): Response|StreamedResponse|BinaryFileResponse
    {
        $path = ltrim($path, '/');

        abort_if($path === '' || str_contains($path, '..'), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        abort_unless(in_array($extension, self::ALLOWED_EXTENSIONS, true), 404);

        $disk = Storage::disk(config('gearora.uploads.public_disk'));

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=604800',
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
        ]);
    }
}
