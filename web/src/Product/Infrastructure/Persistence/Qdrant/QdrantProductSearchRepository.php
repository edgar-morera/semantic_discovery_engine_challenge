<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Persistence\Qdrant;

use App\Product\Domain\Model\Product;
use App\Product\Domain\Port\ProductSearchPort;
use App\Product\Domain\ValueObject\Embedding;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class QdrantProductSearchRepository implements ProductSearchPort
{
    private const string COLLECTION = 'products';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $qdrantDsn,
    ) {}

    public function index(Product $product, Embedding $embedding): void
    {
        $this->ensureCollectionExists();

        $this->httpClient->request(
            'PUT',
            $this->qdrantDsn . '/collections/' . self::COLLECTION . '/points',
            [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => [
                    'points' => [
                        [
                            'id'      => $product->productId()->value(),
                            'vector'  => $embedding->values(),
                            'payload' => [
                                'name'                => $product->productName()->value(),
                                'semantic_description' => $product->semanticDescription()->value(),
                            ],
                        ],
                    ],
                ],
            ],
        )->getStatusCode();
    }

    private function ensureCollectionExists(): void
    {
        $statusCode = $this->httpClient->request(
            'GET',
            $this->qdrantDsn . '/collections/' . self::COLLECTION,
        )->getStatusCode();

        if (404 === $statusCode) {
            $this->httpClient->request(
                'PUT',
                $this->qdrantDsn . '/collections/' . self::COLLECTION,
                [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json'    => [
                        'vectors' => [
                            'size'     => Embedding::DIMENSIONS,
                            'distance' => 'Cosine',
                        ],
                    ],
                ],
            )->getStatusCode();
        }
    }
}
