<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Persistence\Qdrant;

use App\Product\Domain\Model\Product;
use App\Product\Domain\ValueObject\Embedding;
use App\Product\Domain\ValueObject\ProductId;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use App\Product\Domain\ValueObject\SearchLimit;
use App\Product\Domain\ValueObject\SearchResult;
use App\Product\Infrastructure\Persistence\Qdrant\QdrantProductSearchRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class QdrantProductSearchRepositoryTest extends TestCase
{
    private const string UUID_A = '550e8400-e29b-41d4-a716-446655440000';
    private const string UUID_B = '660e8400-e29b-41d4-a716-446655440001';

    private HttpClientInterface&MockObject $httpClient;
    private QdrantProductSearchRepository $repository;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->repository = new QdrantProductSearchRepository($this->httpClient, 'http://qdrant:6333');
    }

    public function testSearchCallsQdrantWithVectorAndLimit(): void
    {
        $vector = array_fill(0, 384, 0.1);
        $embedding = new Embedding($vector);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'result' => [
                ['id' => self::UUID_A, 'score' => 0.95, 'payload' => ['name' => 'Shoes', 'semantic_description' => 'Trail running shoes']],
                ['id' => self::UUID_B, 'score' => 0.82, 'payload' => ['name' => 'Jacket', 'semantic_description' => 'Waterproof jacket']],
            ],
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'http://qdrant:6333/collections/products/points/search',
                $this->callback(fn (array $opts) => $opts['json']['vector'] === $vector
                    && 5 === $opts['json']['limit']
                    && true === $opts['json']['with_payload']
                ),
            )
            ->willReturn($response);

        $results = $this->repository->search($embedding, new SearchLimit(5));

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(SearchResult::class, $results);
        $this->assertSame(self::UUID_A, $results[0]->productId->value());
        $this->assertSame('Shoes', $results[0]->name->value());
        $this->assertSame('Trail running shoes', $results[0]->semanticDescription->value());
        $this->assertSame(0.95, $results[0]->score);
        $this->assertSame(self::UUID_B, $results[1]->productId->value());
        $this->assertSame(0.82, $results[1]->score);
    }

    public function testSearchReturnsEmptyArrayWhenNoResults(): void
    {
        $embedding = new Embedding(array_fill(0, 384, 0.1));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['result' => []]);

        $this->httpClient->method('request')->willReturn($response);

        $results = $this->repository->search($embedding, new SearchLimit(10));

        $this->assertSame([], $results);
    }

    public function testIndexSendsCorrectPayloadToQdrant(): void
    {
        $product = Product::create(
            new ProductId(self::UUID_A),
            new ProductName('Trail Shoes'),
            new ProductSemanticDescription('Lightweight trail running shoes'),
        );
        $vector = array_fill(0, 384, 0.3);
        $embedding = new Embedding($vector);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'http://qdrant:6333/collections/products/points',
                $this->callback(function (array $opts) use ($vector): bool {
                    $point = $opts['json']['points'][0];

                    return self::UUID_A === $point['id']
                        && $vector === $point['vector']
                        && 'Trail Shoes' === $point['payload']['name']
                        && 'Lightweight trail running shoes' === $point['payload']['semantic_description'];
                }),
            )
            ->willReturn($response);

        $this->repository->index($product, $embedding);
    }
}
