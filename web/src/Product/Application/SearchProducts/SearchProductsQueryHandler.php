<?php

declare(strict_types=1);

namespace App\Product\Application\SearchProducts;

use App\Product\Domain\Port\EmbeddingService;
use App\Product\Domain\Port\ProductSearchPort;
use App\Product\Domain\Repository\ProductRepository;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class SearchProductsQueryHandler
{
    public function __construct(
        private readonly EmbeddingService $embeddingService,
        private readonly ProductSearchPort $productSearchPort,
        private readonly ProductRepository $productRepository,
    ) {}

    /**
     * @return SearchProductsResponse[]
     */
    public function __invoke(SearchProductsQuery $query): array
    {
        $embedding = $this->embeddingService->generate(
            new ProductSemanticDescription($query->queryText),
        );

        $searchResults = $this->productSearchPort->search($embedding, $query->limit);

        $responses = [];
        foreach ($searchResults as $result) {
            $product = $this->productRepository->findById($result->productId);

            if (null === $product) {
                continue;
            }

            $responses[] = new SearchProductsResponse(
                id: $result->productId->value(),
                name: $product->productName()->value(),
                semanticDescription: $product->semanticDescription()->value(),
                score: $result->score,
            );
        }

        return $responses;
    }
}
