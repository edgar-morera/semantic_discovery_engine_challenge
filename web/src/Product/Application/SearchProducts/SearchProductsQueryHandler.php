<?php

declare(strict_types=1);

namespace App\Product\Application\SearchProducts;

use App\Product\Domain\Port\EmbeddingService;
use App\Product\Domain\Port\ProductSearchPort;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class SearchProductsQueryHandler
{
    public function __construct(
        private readonly EmbeddingService $embeddingService,
        private readonly ProductSearchPort $productSearchPort,
    ) {
    }

    /**
     * @return SearchProductsResponse[]
     */
    public function __invoke(SearchProductsQuery $query): array
    {
        $embedding = $this->embeddingService->generate(
            new ProductSemanticDescription($query->queryText),
        );

        return array_map(
            static fn ($result) => new SearchProductsResponse(
                id: $result->productId->value(),
                name: $result->name,
                semanticDescription: $result->semanticDescription,
                score: $result->score,
            ),
            $this->productSearchPort->search($embedding, $query->limit),
        );
    }
}
