<?php

declare(strict_types=1);

namespace App\Product\Domain\Port;

use App\Product\Domain\Model\Product;
use App\Product\Domain\ValueObject\Embedding;
use App\Product\Domain\ValueObject\ProductId;

interface ProductSearchPort
{
    public function index(Product $product, Embedding $embedding): void;

    /**
     * @return ProductId[]
     */
    public function search(Embedding $query, int $limit): array;
}
