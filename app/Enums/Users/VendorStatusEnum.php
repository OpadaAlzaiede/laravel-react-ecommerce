<?php

namespace App\Enums\Users;

enum VendorStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public static function labels(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::APPROVED->value => 'Approved',
            self::REJECTED->value => 'Rejected',
        ];
    }

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public static function colors(): array
    {
        return [
            'gray' => self::PENDING->value,
            'success' => self::APPROVED->value,
            'danger' => self::REJECTED->value,
        ];
    }
}
