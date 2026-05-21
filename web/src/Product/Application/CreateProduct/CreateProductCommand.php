<?php

declare(strict_types=1);

namespace App\Product\Application\CreateProduct;

final readonly class CreateProductCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $semanticDescription,
    ) {
    }
}
