<?php

declare(strict_types=1);

namespace App\Product\Domain\Repository;

use App\Product\Domain\Model\Product;
use App\Product\Domain\ValueObject\Embedding;

interface ProductSearchRepository
{
    public function index(Product $product, Embedding $embedding): void;
}
