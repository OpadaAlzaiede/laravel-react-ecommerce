<?php

namespace App\Enums\Products;

enum ProductVariationTypeEnum: string
{
    case SELECT = 'select';
    case RADIO = 'radio';
    case IMAGE = 'image';

    public static function labels(): array
    {
        return [
            self::SELECT->value => __('Select'),
            self::RADIO->value => __('Radio'),
            self::IMAGE->value => __('Image'),
        ];
    }
}
