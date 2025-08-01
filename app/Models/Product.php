<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Products\ProductStatusEnum;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(100)->height(232);
        $this->addMediaConversion('small')->width(480)->height(232);
        $this->addMediaConversion('large')->width(1200)->height(232);
    }

    public function scopeVendor(Builder $query): Builder
    {
        return $query->where('created_by', auth()->user()->id);
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

    public function getPriceForOptions($optionIds = [])
    {
        $optionIds = array_values($optionIds);
        sort($optionIds);

        foreach($this->variations as $variation) {
            $a = $variation->variation_type_option_ids;
            sort($a);
            if($optionIds == $a) {
                return $variation->price !== null ? $variation->price : $this->price;
            }
        }

        return $this->price;
    }

    public function getPriceForFirstOption(): float
    {
        $firstOption = $this->getFirstOptionsMap();

        if($firstOption) {
            return $this->getPriceForOptions($firstOption);
        }

        return $this->price;
    }

    public function getFirstImageUrl($collectionName = 'images', $conversion = 'small'): string
    {
        if($this->options->count() > 0) {
            foreach($this->options as $option) {
                $imageUrl = $option->getFirstMediaUrl($collectionName, $conversion);
                if($imageUrl) {
                    return $imageUrl;
                }
            }
        }

        return $this->getFirstMediaUrl($collectionName, $conversion);
    }

    public function getImageForOptions($optionIds = [])
    {
        if($optionIds) {
            $optionIds = array_values($optionIds);
            sort($optionIds);
            $options = VariationTypeOption::whereIn('id', $optionIds)->get();

            foreach($options as $option) {
                $media = $option->getFirstMediaUrl('images', 'small');
                if($media) {
                    return $media;
                }
            }
        }

        return $this->getFirstMediaUrl('images', 'small');
    }

    public function getImages(): MediaCollection
    {
        if($this->options->count() > 0) {
            foreach($this->options as $option) {
                $images = $option->getMedia('images');
                if($images) {
                    return $images;
                }
            }
        }

        return $this->getMedia('images');
    }

    public function getFirstOptionsMap(): array
    {
        return $this->variationTypes
                ->mapWithKeys(fn($type) => [$type->id => $type->options[0]?->id])
                ->toArray();
    }
}
