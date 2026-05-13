<?php

declare(strict_types=1);

namespace App\Product\Domain\Service;

use App\Product\Domain\ValueObject\Embedding;
use App\Product\Domain\ValueObject\ProductSemanticDescription;

interface EmbeddingService
{
    public function generate(ProductSemanticDescription $description): Embedding;
}
