<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Product;
use App\Models\VariationType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class CompleteOptionSelection implements ValidationRule
{
    public function __construct(private readonly Product $product) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || $value === []) {
            return;
        }

        $optionIdsByType = $this->product->variationTypes()
            ->with('options:id,variation_type_id')
            ->get()
            ->mapWithKeys(static fn (VariationType $type): array => [$type->id => $type->options->modelKeys()]);

        $selectsEveryType = count($value) === $optionIdsByType->count();

        $everyOptionBelongsToItsType = collect($value)->every(
            static fn (mixed $optionId, int|string $typeId): bool => in_array((int) $optionId, $optionIdsByType->get((int) $typeId, []), true),
        );

        if (! $selectsEveryType || ! $everyOptionBelongsToItsType) {
            $fail('The selected options are not valid for this product.');
        }
    }
}
