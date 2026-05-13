<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Http;

use App\Product\Application\CreateProduct\CreateProductCommand;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Products')]
final class CreateProductController
{
    public function __construct(private readonly MessageBusInterface $commandBus) {}

    #[Route('/products', name: 'product_create', methods: ['POST'])]
    #[OA\Post(
        path: '/products',
        summary: 'Create a new product',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'semanticDescription'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Trail Running Shoes X200'),
                    new OA\Property(property: 'semanticDescription', type: 'string', example: 'Lightweight carbon plate trail running shoes for mountain races'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Product created',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'id', type: 'string', format: 'uuid')]
                )
            ),
            new OA\Response(response: 400, description: 'Missing required fields'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string, mixed>|null $body */
        $body = json_decode($request->getContent(), true);

        if (!isset($body['name'], $body['semanticDescription'])) {
            return new JsonResponse(
                ['error' => 'Missing required fields: name, semanticDescription'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $command = new CreateProductCommand(
            name: (string) $body['name'],
            semanticDescription: (string) $body['semanticDescription'],
        );

        $this->commandBus->dispatch($command);

        return new JsonResponse(['id' => $command->id], Response::HTTP_CREATED);
    }
}
