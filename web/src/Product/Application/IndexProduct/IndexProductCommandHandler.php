<?php

declare(strict_types=1);

namespace App\Product\Application\IndexProduct;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Port\EmbeddingService;
use App\Product\Domain\Port\ProductSearchPort;
use App\Product\Domain\Repository\ProductRepository;
use App\Product\Domain\ValueObject\ProductId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class IndexProductCommandHandler
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly EmbeddingService $embeddingService,
        private readonly ProductSearchPort $productSearchRepository,
    ) {
    }

    public function __invoke(IndexProductCommand $command): void
    {
        $productId = new ProductId($command->productId);
        $product = $this->productRepository->findById($productId);

        if (null === $product) {
            throw new ProductNotFoundException($command->productId);
        }

        $embedding = $this->embeddingService->generate($product->semanticDescription());

        $product->assignEmbedding($embedding);

        $this->productSearchRepository->index($product, $embedding);
    }
}
