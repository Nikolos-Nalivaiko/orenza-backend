<?php

declare(strict_types=1);

namespace App\Actions\Materials\Concerns;

use App\Enums\MaterialBuyer;

trait PricesTheBuyer
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prices(MaterialBuyer $buyer, array $attributes): array
    {
        if ($buyer->isClient()) {
            return ['cost_price' => null, 'client_price' => null];
        }

        return array_filter(
            [
                'cost_price' => $attributes['cost_price'] ?? null,
                'client_price' => $attributes['client_price'] ?? null,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
