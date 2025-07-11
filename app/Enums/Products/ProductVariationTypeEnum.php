<?php

namespace App\Enums\Products;

enum ProductVariationTypeEnum: string
{
    case RADIO = 'radio';
    case IMAGE = 'image';

    public static function labels(): array
    {
        return [
            self::RADIO->value => __('Radio'),
            self::IMAGE->value => __('Image'),
        ];
    }
}
