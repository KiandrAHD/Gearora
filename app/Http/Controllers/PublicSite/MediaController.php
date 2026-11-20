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
     * Hanya raster yang benar-benar diunggah aplikasi. SVG/GIF sengaja tidak
     * diizinkan karena SVG dapat memuat script (stored XSS) pada origin yang sama.
     *
     * @var array<int, string>
     */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function show(Request $request, string $path): Response|StreamedResponse|BinaryFileResponse
    {
        $path = ltrim($path, '/');

        // Tolak traversal (plain maupun ter-encode) dan karakter berbahaya.
        abort_if(
            $path === ''
            || str_contains($path, '..')
            || str_contains($path, '\\')
            || str_contains($path, "\0")
            || preg_match('#(^|/)[A-Za-z0-9_\-]*\.(php|phtml|phar|htaccess)#i', $path) === 1,
            404,
        );

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        abort_unless(in_array($extension, self::ALLOWED_EXTENSIONS, true), 404);

        $disk = Storage::disk(config('gearora.uploads.public_disk'));

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=604800',
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'none'; sandbox",
        ]);
    }
}
