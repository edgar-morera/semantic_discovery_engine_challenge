<?php

declare(strict_types=1);

namespace App\Product\Domain\Port;

use App\Product\Domain\Model\Product;
use App\Product\Domain\ValueObject\Embedding;

interface ProductSearchPort
{
    public function index(Product $product, Embedding $embedding): void;
}
