<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Http;

use App\Product\Application\IndexProduct\IndexProductCommand;
use App\Product\Domain\Exception\ProductNotFoundException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Products')]
final class IndexProductController
{
    public function __construct(private readonly MessageBusInterface $commandBus) {}

    #[Route('/products/{id}/index', name: 'product_index', methods: ['POST'])]
    #[OA\Post(
        path: '/products/{id}/index',
        summary: 'Generate and store the semantic embedding for a product',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Product indexed successfully'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new IndexProductCommand($id));
        } catch (ProductNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
