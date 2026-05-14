<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Http;

use App\Product\Application\SearchProducts\SearchProductsQuery;
use App\Product\Application\SearchProducts\SearchProductsResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Products')]
final class SearchProductsController
{
    private const int DEFAULT_LIMIT = 10;
    private const int MAX_LIMIT = 50;

    public function __construct(
        #[Target('query.bus')]
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    #[Route('/products/search', name: 'product_search', methods: ['GET'])]
    #[OA\Get(
        path: '/products/search',
        summary: 'Search products by semantic similarity',
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string'), description: 'Search query text'),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, maximum: 50), description: 'Max number of results'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of matching products ordered by relevance',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'semanticDescription', type: 'string'),
                            new OA\Property(property: 'score', type: 'number', format: 'float'),
                        ]
                    )
                )
            ),
            new OA\Response(response: 400, description: 'Missing required parameter q'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $queryText = $request->query->get('q');

        if (null === $queryText || '' === $queryText) {
            return new JsonResponse(
                ['error' => 'Missing required query parameter: q'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $limit = min(self::MAX_LIMIT, max(1, (int) $request->query->get('limit', self::DEFAULT_LIMIT)));

        $envelope = $this->queryBus->dispatch(new SearchProductsQuery($queryText, $limit));

        $stamp = $envelope->last(HandledStamp::class);

        /** @var SearchProductsResponse[] $results */
        $results = $stamp instanceof HandledStamp ? $stamp->getResult() : [];

        return new JsonResponse(
            array_map(
                static fn (SearchProductsResponse $r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'semanticDescription' => $r->semanticDescription,
                    'score' => $r->score,
                ],
                $results,
            ),
            Response::HTTP_OK,
        );
    }
}
