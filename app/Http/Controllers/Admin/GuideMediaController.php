<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EquipmentGuide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Upload gambar inline untuk Studio Panduan.
 *
 * Hanya staf dengan izin membuat/mengubah panduan yang boleh mengunggah.
 * File disimpan dengan nama acak pada disk publik dan disajikan lewat endpoint
 * `/media/{path}` yang memvalidasi ekstensi serta menolak traversal.
 */
class GuideMediaController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->authorizeUpload($request);

        $maxKb = (int) config('gearora.guides.inline_max_kb', 4096);
        $mimes = implode(',', (array) config('gearora.guides.cover_mimes', ['image/jpeg', 'image/png', 'image/webp']));

        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'mimetypes:'.$mimes, 'max:'.$maxKb],
            'alt' => ['nullable', 'string', 'max:160'],
        ]);

        $file = $validated['image'];
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
        $directory = (string) config('gearora.guides.inline_directory', 'guide-inline');
        $path = $file->storeAs($directory, Str::random(40).'.'.$extension, [
            'disk' => config('gearora.guides.cover_disk', 'public'),
            'visibility' => 'public',
        ]);

        abort_if($path === false, 500, 'Gagal menyimpan gambar.');

        return response()->json([
            'path' => $path,
            'url' => route('media.show', ['path' => $path], false),
            'alt' => (string) ($validated['alt'] ?? ''),
        ], 201);
    }

    private function authorizeUpload(Request $request): void
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $allowed = $user->can('create', EquipmentGuide::class)
            || $user->can('update', new EquipmentGuide);

        abort_unless($allowed, 403);
    }
}
