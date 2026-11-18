<?php

namespace App\Models;

use App\Enums\GuideStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentGuide extends Model
{
    use HasFactory;

    protected $fillable = [
        'guide_category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'content_format',
        'cover_image_path',
        'status',
        'author_id',
        'publisher_id',
        'published_at',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => GuideStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<GuideCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(GuideCategory::class, 'guide_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }

    /**
     * @return HasMany<GuideSource, $this>
     */
    public function sources(): HasMany
    {
        return $this->hasMany(GuideSource::class);
    }

    /**
     * @param  Builder<EquipmentGuide>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', GuideStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<EquipmentGuide>  $query
     */
    public function scopeSearch(Builder $query, ?string $keyword): void
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return;
        }

        $query->where(function (Builder $inner) use ($keyword) {
            $inner->where('title', 'like', '%'.$keyword.'%')
                ->orWhere('excerpt', 'like', '%'.$keyword.'%')
                ->orWhere('content', 'like', '%'.$keyword.'%');
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function displayCoverPath(): ?string
    {
        return $this->cover_image_path;
    }
}
