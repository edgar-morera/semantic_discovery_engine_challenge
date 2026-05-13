<?php

declare(strict_types=1);

namespace App\Product\Application\CreateProduct;

use Symfony\Component\Uid\Uuid;

final readonly class CreateProductCommand
{
    public string $id;

    public function __construct(
        public readonly string $name,
        public readonly string $semanticDescription,
    ) {
        $this->id = Uuid::v4()->toRfc4122();
    }
}
