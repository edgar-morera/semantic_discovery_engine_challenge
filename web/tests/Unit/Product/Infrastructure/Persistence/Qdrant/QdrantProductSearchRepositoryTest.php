<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Persistence\Qdrant;

use App\Product\Domain\ValueObject\Embedding;
use App\Product\Domain\ValueObject\ProductId;
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

    public function test_search_calls_qdrant_with_vector_and_limit(): void
    {
        $vector   = array_fill(0, Embedding::DIMENSIONS, 0.1);
        $embedding = new Embedding($vector);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'result' => [
                ['id' => self::UUID_A, 'score' => 0.95],
                ['id' => self::UUID_B, 'score' => 0.82],
            ],
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'http://qdrant:6333/collections/products/points/search',
                $this->callback(fn (array $opts) =>
                    $opts['json']['vector'] === $vector &&
                    $opts['json']['limit'] === 5 &&
                    $opts['json']['with_payload'] === false
                ),
            )
            ->willReturn($response);

        $results = $this->repository->search($embedding, 5);

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(SearchResult::class, $results);
        $this->assertSame(self::UUID_A, $results[0]->productId->value());
        $this->assertSame(0.95, $results[0]->score);
        $this->assertSame(self::UUID_B, $results[1]->productId->value());
        $this->assertSame(0.82, $results[1]->score);
    }

    public function test_search_returns_empty_array_when_no_results(): void
    {
        $embedding = new Embedding(array_fill(0, Embedding::DIMENSIONS, 0.1));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['result' => []]);

        $this->httpClient->method('request')->willReturn($response);

        $results = $this->repository->search($embedding, 10);

        $this->assertSame([], $results);
    }
}
