<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Http;

use App\Product\Application\IndexProduct\IndexProductCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Products')]
final class IndexProductController
{
    public function __construct(private readonly MessageBusInterface $commandBus)
    {
    }

    #[Route('/products/{id}/index', name: 'product_index', methods: ['POST'])]
    #[OA\Post(
        path: '/products/{id}/index',
        summary: 'Enqueue the semantic embedding generation for a product',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 202, description: 'Indexing job accepted and enqueued'),
        ]
    )]
    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new IndexProductCommand($id));

        return new JsonResponse(null, Response::HTTP_ACCEPTED);
    }
}
