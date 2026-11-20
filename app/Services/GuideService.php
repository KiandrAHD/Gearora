<?php

namespace App\Services;

use App\Enums\GuideStatus;
use App\Models\EquipmentGuide;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pengelolaan artikel panduan, kategori, sumber, dan siklus publikasi.
 */
class GuideService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): EquipmentGuide
    {
        return DB::transaction(function () use ($data, $actor) {
            $attributes = $this->attributes($data);
            $attributes['author_id'] = $actor->getKey();
            $attributes['publisher_id'] = $data['status'] === GuideStatus::Published->value ? $actor->getKey() : null;
            $attributes['published_at'] = $data['status'] === GuideStatus::Published->value ? now() : null;

            /** @var EquipmentGuide $guide */
            $guide = EquipmentGuide::query()->create($attributes);

            $this->syncSources($guide, (array) ($data['sources'] ?? []));

            $this->logger->log('guide.created', $guide, "Panduan {$guide->title} dibuat.", ['status' => $guide->status->value], $actor);

            return $guide->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EquipmentGuide $guide, array $data, User $actor): EquipmentGuide
    {
        return DB::transaction(function () use ($guide, $data, $actor) {
            $guide = EquipmentGuide::query()->whereKey($guide->getKey())->lockForUpdate()->firstOrFail();

            $attributes = $this->attributes($data);
            $targetStatus = GuideStatus::from((string) $data['status']);

            if ($targetStatus === GuideStatus::Published && $guide->status !== GuideStatus::Published) {
                // Publikasi lewat form tetap membutuhkan izin publish.
                $attributes['published_at'] = now();
                $attributes['publisher_id'] = $actor->getKey();
            }

            if ($targetStatus === GuideStatus::Draft) {
                $attributes['published_at'] = null;
            }

            $guide->fill($attributes)->save();
            $this->syncSources($guide, (array) ($data['sources'] ?? []));

            $this->logger->log('guide.updated', $guide, "Panduan {$guide->title} diperbarui.", ['status' => $guide->status->value], $actor);

            return $guide->refresh();
        });
    }

    public function publish(EquipmentGuide $guide, User $actor): EquipmentGuide
    {
        return DB::transaction(function () use ($guide, $actor) {
            $guide = EquipmentGuide::query()->whereKey($guide->getKey())->lockForUpdate()->firstOrFail();

            $guide->forceFill([
                'status' => GuideStatus::Published,
                'published_at' => $guide->published_at ?? now(),
                'publisher_id' => $actor->getKey(),
            ])->save();

            $this->logger->log('guide.published', $guide, "Panduan {$guide->title} diterbitkan.", [], $actor);

            return $guide->refresh();
        });
    }

    public function unpublish(EquipmentGuide $guide, User $actor): EquipmentGuide
    {
        return DB::transaction(function () use ($guide, $actor) {
            $guide = EquipmentGuide::query()->whereKey($guide->getKey())->lockForUpdate()->firstOrFail();

            $guide->forceFill([
                'status' => GuideStatus::Draft,
                'published_at' => null,
            ])->save();

            $this->logger->log('guide.unpublished', $guide, "Panduan {$guide->title} ditarik menjadi draf.", [], $actor);

            return $guide->refresh();
        });
    }

    public function archive(EquipmentGuide $guide, User $actor): EquipmentGuide
    {
        return DB::transaction(function () use ($guide, $actor) {
            $guide = EquipmentGuide::query()->whereKey($guide->getKey())->lockForUpdate()->firstOrFail();

            $guide->forceFill(['status' => GuideStatus::Archived])->save();

            $this->logger->log('guide.archived', $guide, "Panduan {$guide->title} diarsipkan.", [], $actor);

            return $guide->refresh();
        });
    }

    public function delete(EquipmentGuide $guide, User $actor): void
    {
        DB::transaction(function () use ($guide, $actor) {
            $title = $guide->title;
            $guide->delete();

            $this->logger->log('guide.deleted', null, "Panduan {$title} dihapus.", ['title' => $title], $actor);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $attributes = Arr::only($data, [
            'guide_category_id',
            'title',
            'slug',
            'excerpt',
            'content',
            'status',
            'seo_title',
            'seo_description',
        ]);

        $attributes['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $attributes['content_format'] = 'markdown';

        if (($data['cover'] ?? null) instanceof UploadedFile) {
            $attributes['cover_image_path'] = $this->storeCover($data['cover']);
        }

        return $attributes;
    }

    private function storeCover(UploadedFile $file): string
    {
        $directory = (string) config('gearora.guides.cover_directory', 'guide-covers');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $filename = Str::random(40).'.'.$extension;

        return $file->storeAs($directory, $filename, [
            'disk' => config('gearora.guides.cover_disk', 'public'),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $sources
     */
    private function syncSources(EquipmentGuide $guide, array $sources): void
    {
        $clean = collect($sources)
            ->filter(fn ($source) => filled($source['source_title'] ?? null))
            ->map(fn ($source) => [
                'source_title' => trim((string) $source['source_title']),
                'publisher' => filled($source['publisher'] ?? null) ? trim((string) $source['publisher']) : null,
                'source_url' => filled($source['source_url'] ?? null) ? trim((string) $source['source_url']) : null,
                'accessed_at' => filled($source['accessed_at'] ?? null) ? $source['accessed_at'] : null,
                'notes' => filled($source['notes'] ?? null) ? trim((string) $source['notes']) : null,
            ])
            ->values();

        $guide->sources()->delete();

        foreach ($clean as $source) {
            $guide->sources()->create($source);
        }
    }

    public function deleteCover(EquipmentGuide $guide): void
    {
        if (filled($guide->cover_image_path)) {
            Storage::disk(config('gearora.guides.cover_disk', 'public'))->delete($guide->cover_image_path);
        }
    }
}
