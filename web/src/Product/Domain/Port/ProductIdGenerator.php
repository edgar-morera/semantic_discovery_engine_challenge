<?php

declare(strict_types=1);

namespace App\Product\Domain\Port;

use App\Product\Domain\ValueObject\ProductId;

interface ProductIdGenerator
{
    public function generate(): ProductId;
}
