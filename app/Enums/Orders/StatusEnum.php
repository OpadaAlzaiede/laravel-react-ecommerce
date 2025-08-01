<?php

namespace App\Enums\Orders;

enum StatusEnum: string
{
    case DRAFT = 'draft';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';


    public static function labels()
    {
        return [
            self::DRAFT->value => 'Draft',
            self::PAID->value => 'Paid',
            self::SHIPPED->value => 'Shipped',
            self::DELIVERED->value => 'Delivered',
            self::CANCELLED->value => 'Cancelled'
        ];
    }

    public static function colors(): array
    {
        return [
            'gray' => self::DRAFT->value,
            'success' => self::PAID->value,
        ];
    }
}
