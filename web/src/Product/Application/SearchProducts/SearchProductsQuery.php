<?php

declare(strict_types=1);

namespace App\Product\Application\SearchProducts;

final readonly class SearchProductsQuery
{
    public function __construct(
        public string $queryText,
        public int $limit = 10,
    ) {
    }
}
