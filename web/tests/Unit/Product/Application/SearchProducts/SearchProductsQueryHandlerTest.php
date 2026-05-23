<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Application\SearchProducts;

use App\Product\Application\SearchProducts\SearchProductsQuery;
use App\Product\Application\SearchProducts\SearchProductsQueryHandler;
use App\Product\Application\SearchProducts\SearchProductsResponse;
use App\Product\Domain\Port\EmbeddingService;
use App\Product\Domain\Port\ProductSearchPort;
use App\Product\Domain\ValueObject\Embedding;
use App\Product\Domain\ValueObject\ProductId;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use App\Product\Domain\ValueObject\SearchResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SearchProductsQueryHandlerTest extends TestCase
{
    private const string UUID_A = '550e8400-e29b-41d4-a716-446655440000';
    private const string UUID_B = '660e8400-e29b-41d4-a716-446655440001';

    private EmbeddingService&MockObject $embeddingService;
    private ProductSearchPort&MockObject $productSearchPort;
    private SearchProductsQueryHandler $handler;

    protected function setUp(): void
    {
        $this->embeddingService = $this->createMock(EmbeddingService::class);
        $this->productSearchPort = $this->createMock(ProductSearchPort::class);

        $this->handler = new SearchProductsQueryHandler(
            $this->embeddingService,
            $this->productSearchPort,
        );
    }

    public function testMapsSearchResultsToResponsesPreservingOrder(): void
    {
        $embedding = new Embedding(array_fill(0, Embedding::DIMENSIONS, 0.1));

        $this->embeddingService->method('generate')->willReturn($embedding);

        $this->productSearchPort
            ->expects($this->once())
            ->method('search')
            ->with($embedding, 10)
            ->willReturn([
                new SearchResult(new ProductId(self::UUID_A), new ProductName('Shoes'), new ProductSemanticDescription('Trail running shoes'), 0.95),
                new SearchResult(new ProductId(self::UUID_B), new ProductName('Jacket'), new ProductSemanticDescription('Waterproof jacket'), 0.82),
            ]);

        $result = ($this->handler)(new SearchProductsQuery('running shoes'));

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(SearchProductsResponse::class, $result);
        $this->assertSame(self::UUID_A, $result[0]->id);
        $this->assertSame('Shoes', $result[0]->name);
        $this->assertSame(0.95, $result[0]->score);
        $this->assertSame(self::UUID_B, $result[1]->id);
        $this->assertSame(0.82, $result[1]->score);
        $this->assertGreaterThan($result[1]->score, $result[0]->score);
    }

    public function testReturnsEmptyArrayWhenNoResults(): void
    {
        $embedding = new Embedding(array_fill(0, Embedding::DIMENSIONS, 0.1));

        $this->embeddingService->method('generate')->willReturn($embedding);
        $this->productSearchPort->method('search')->willReturn([]);

        $result = ($this->handler)(new SearchProductsQuery('no match'));

        $this->assertSame([], $result);
    }

    public function testRespectsLimitParameter(): void
    {
        $embedding = new Embedding(array_fill(0, Embedding::DIMENSIONS, 0.1));

        $this->embeddingService->method('generate')->willReturn($embedding);

        $this->productSearchPort
            ->expects($this->once())
            ->method('search')
            ->with($embedding, 5)
            ->willReturn([]);

        ($this->handler)(new SearchProductsQuery('test', 5));
    }
}
