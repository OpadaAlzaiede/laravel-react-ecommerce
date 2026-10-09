<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Products\ProductStatusEnum;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'price',
        'status',
        'quantity',
        'is_featured',
        'department_id',
        'category_id',
        'currency_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => Str::sanitizeHtml((string) $value),
        );
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(100)->height(232);
        $this->addMediaConversion('small')->width(480)->height(232);
        $this->addMediaConversion('large')->width(1200)->height(232);
    }

    public function scopeVendor(Builder $query): Builder
    {
        return $query->where('created_by', auth()->id());
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatusEnum::PUBLISHED->value);
    }

    public function scopeForWebsite(Builder $query): Builder
    {
        return $query->published();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function variationTypes(): HasMany
    {
        return $this->hasMany(VariationType::class);
    }

    public function options(): HasManyThrough
    {
        return $this->hasManyThrough(
            VariationTypeOption::class,
            VariationType::class,
            'product_id',
            'variation_type_id',
            'id',
            'id'
        );
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    public function getPriceForOptions(array $optionIds = []): float
    {
        return (float) ($this->variationForOptions($optionIds)?->price ?? $this->price);
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    public function getStockForOptions(array $optionIds = []): ?int
    {
        if ($optionIds === []) {
            return $this->quantity;
        }

        return $this->variationForOptions($optionIds)?->quantity;
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    public function variationForOptions(array $optionIds): ?ProductVariation
    {
        $optionIds = array_values($optionIds);
        sort($optionIds);

        return $this->variations->first(static function (ProductVariation $variation) use ($optionIds): bool {
            $variationOptionIds = $variation->variation_type_option_ids;
            sort($variationOptionIds);

            return $optionIds == $variationOptionIds;
        });
    }

    public function getPriceForFirstOption(): float
    {
        $firstOption = $this->getFirstOptionsMap();

        if ($firstOption) {
            return $this->getPriceForOptions($firstOption);
        }

        return (float) $this->price;
    }

    public function getFirstImageUrl(string $collectionName = 'images', string $conversion = 'small'): string
    {
        foreach ($this->options as $option) {
            $imageUrl = $option->getFirstMediaUrl($collectionName, $conversion);

            if ($imageUrl) {
                return $imageUrl;
            }
        }

        return $this->getFirstMediaUrl($collectionName, $conversion);
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    public function getImageForOptions(array $optionIds = []): string
    {
        if ($optionIds) {
            $optionIds = array_values($optionIds);
            sort($optionIds);
            $options = VariationTypeOption::whereIn('id', $optionIds)->get();

            foreach ($options as $option) {
                $media = $option->getFirstMediaUrl('images', 'small');

                if ($media) {
                    return $media;
                }
            }
        }

        return $this->getFirstMediaUrl('images', 'small');
    }

    public function getImages(): MediaCollection
    {
        foreach ($this->options as $option) {
            $images = $option->getMedia('images');

            if ($images) {
                return $images;
            }
        }

        return $this->getMedia('images');
    }

    /**
     * @return array<int, int|null>
     */
    public function getFirstOptionsMap(): array
    {
        return $this->variationTypes
            ->mapWithKeys(static fn (VariationType $type): array => [$type->id => $type->options[0]?->id])
            ->toArray();
    }
}
