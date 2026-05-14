<?php

declare(strict_types=1);

namespace App\Product\Application\SearchProducts;

final readonly class SearchProductsResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $semanticDescription,
        public float $score,
    ) {}
}
