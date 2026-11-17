<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'brand',
        'type',
        'status',
        'specifications',
        'requires_deposit',
        'deposit_amount',
        'rental_price_per_day',
        'sale_price',
        'is_featured',
        'thumbnail_path',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'status' => ProductStatus::class,
            'specifications' => 'array',
            'requires_deposit' => 'boolean',
            'is_featured' => 'boolean',
            'deposit_amount' => 'decimal:2',
            'rental_price_per_day' => 'decimal:2',
            'sale_price' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    /**
     * @return HasOne<ProductImage, $this>
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', ProductStatus::Active->value);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeRental(Builder $query): void
    {
        $query->whereIn('type', [ProductType::Rental->value, ProductType::Both->value]);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeSale(Builder $query): void
    {
        $query->whereIn('type', [ProductType::Sale->value, ProductType::Both->value]);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    public function supportsRental(): bool
    {
        return $this->type->supportsRental();
    }

    public function supportsSale(): bool
    {
        return $this->type->supportsSale();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function displayImagePath(): ?string
    {
        return $this->thumbnail_path ?: $this->primaryImage?->path;
    }
}
