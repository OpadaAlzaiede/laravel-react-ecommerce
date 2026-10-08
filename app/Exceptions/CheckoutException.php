<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class CheckoutException extends RuntimeException
{
    public static function vendorNotInCart(int $vendorId): self
    {
        return new self("There are no items from vendor {$vendorId} in your cart.");
    }
}
