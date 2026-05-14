<?php

declare(strict_types=1);

namespace App\Product\Application\IndexProduct;

final readonly class IndexProductCommand
{
    public function __construct(public string $productId)
    {
    }
}
