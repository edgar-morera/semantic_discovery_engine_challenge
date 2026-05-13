<?php

declare(strict_types=1);

namespace App\Product\Domain\ValueObject;

final readonly class SearchResult
{
    public function __construct(
        public ProductId $productId,
        public float $score,
    ) {}
}
