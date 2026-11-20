<?php

namespace App\Models;

use App\Enums\TripActivity;
use App\Enums\TripServiceMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aturan rekomendasi Trip Planner. Data-driven (bukan if-else di Blade) dan
 * deterministik: tidak menyimpan kode PHP atau SQL yang dapat dieksekusi.
 */
class TripRecommendationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'activity',
        'min_duration_days',
        'max_duration_days',
        'min_participants',
        'max_participants',
        'required_special_needs',
        'required_experience_levels',
        'product_category_id',
        'product_id',
        'product_variant_id',
        'service_mode',
        'recommended_quantity',
        'quantity_per_participant',
        'priority',
        'reason_template',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'required_special_needs' => 'array',
            'required_experience_levels' => 'array',
            'service_mode' => TripServiceMode::class,
            'min_duration_days' => 'integer',
            'max_duration_days' => 'integer',
            'min_participants' => 'integer',
            'max_participants' => 'integer',
            'recommended_quantity' => 'integer',
            'quantity_per_participant' => 'boolean',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'product_category_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @param  Builder<TripRecommendationRule>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<TripRecommendationRule>  $query
     */
    public function scopeForActivity(Builder $query, string $activity): void
    {
        $query->whereIn('activity', [$activity, TripActivity::ANY]);
    }

    /** @return array<int, string> */
    public function requiredSpecialNeeds(): array
    {
        return array_values(array_filter((array) ($this->required_special_needs ?? [])));
    }

    /** @return array<int, string> */
    public function requiredExperienceLevels(): array
    {
        return array_values(array_filter((array) ($this->required_experience_levels ?? [])));
    }

    public function activityLabel(): string
    {
        if ($this->activity === TripActivity::ANY) {
            return 'Semua aktivitas';
        }

        return TripActivity::tryFrom($this->activity)?->label() ?? $this->activity;
    }
}
