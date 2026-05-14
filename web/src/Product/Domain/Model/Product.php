<?php

declare(strict_types=1);

namespace App\Product\Domain\Model;

use App\Product\Domain\ValueObject\Embedding;
use App\Product\Domain\ValueObject\ProductId;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductSemanticDescription;

final class Product
{
    private ?Embedding $embedding = null;

    private function __construct(
        private readonly ProductId $productId,
        private readonly ProductName $productName,
        private readonly ProductSemanticDescription $semanticDescription,
    ) {
    }

    public static function create(
        ProductId $productId,
        ProductName $productName,
        ProductSemanticDescription $semanticDescription,
    ): self {
        return new self($productId, $productName, $semanticDescription);
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function productName(): ProductName
    {
        return $this->productName;
    }

    public function semanticDescription(): ProductSemanticDescription
    {
        return $this->semanticDescription;
    }

    public function assignEmbedding(Embedding $embedding): void
    {
        $this->embedding = $embedding;
    }

    public function isIndexed(): bool
    {
        return null !== $this->embedding;
    }

    public function embedding(): ?Embedding
    {
        return $this->embedding;
    }
}
