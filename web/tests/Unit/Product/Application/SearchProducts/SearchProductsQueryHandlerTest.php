<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Application\SearchProducts;

use App\Product\Application\SearchProducts\SearchProductsQuery;
use App\Product\Application\SearchProducts\SearchProductsQueryHandler;
use App\Product\Application\SearchProducts\SearchProductsResponse;
use App\Product\Domain\Model\Product;
use App\Product\Domain\Port\EmbeddingService;
use App\Product\Domain\Port\ProductSearchPort;
use App\Product\Domain\Repository\ProductRepository;
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
    private ProductRepository&MockObject $productRepository;
    private SearchProductsQueryHandler $handler;

    protected function setUp(): void
    {
        $this->embeddingService = $this->createMock(EmbeddingService::class);
        $this->productSearchPort = $this->createMock(ProductSearchPort::class);
        $this->productRepository = $this->createMock(ProductRepository::class);

        $this->handler = new SearchProductsQueryHandler(
            $this->embeddingService,
            $this->productSearchPort,
            $this->productRepository,
        );
    }

    public function testReturnsResponsesOrderedByScore(): void
    {
        $embedding = new Embedding(array_fill(0, Embedding::DIMENSIONS, 0.1));
        $productA = $this->make_product(self::UUID_A, 'Shoes', 'Trail running shoes');
        $productB = $this->make_product(self::UUID_B, 'Jacket', 'Waterproof jacket');

        $this->embeddingService->method('generate')->willReturn($embedding);

        $this->productSearchPort
            ->expects($this->once())
            ->method('search')
            ->with($embedding, 10)
            ->willReturn([
                new SearchResult(new ProductId(self::UUID_A), 0.95),
                new SearchResult(new ProductId(self::UUID_B), 0.82),
            ]);

        $this->productRepository
            ->method('findById')
            ->willReturnCallback(fn (ProductId $id) => match ($id->value()) {
                self::UUID_A => $productA,
                self::UUID_B => $productB,
            });

        $result = ($this->handler)(new SearchProductsQuery('running shoes'));

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(SearchProductsResponse::class, $result);
        $this->assertSame(self::UUID_A, $result[0]->id);
        $this->assertSame(0.95, $result[0]->score);
        $this->assertSame(self::UUID_B, $result[1]->id);
        $this->assertSame(0.82, $result[1]->score);
    }

    public function testReturnsEmptyArrayWhenNoResults(): void
    {
        $embedding = new Embedding(array_fill(0, Embedding::DIMENSIONS, 0.1));

        $this->embeddingService->method('generate')->willReturn($embedding);
        $this->productSearchPort->method('search')->willReturn([]);
        $this->productRepository->expects($this->never())->method('findById');

        $result = ($this->handler)(new SearchProductsQuery('no match'));

        $this->assertSame([], $result);
    }

    public function testSkipsProductNotFoundInMysql(): void
    {
        $embedding = new Embedding(array_fill(0, Embedding::DIMENSIONS, 0.1));
        $productA = $this->make_product(self::UUID_A, 'Shoes', 'Trail running shoes');

        $this->embeddingService->method('generate')->willReturn($embedding);

        $this->productSearchPort->method('search')->willReturn([
            new SearchResult(new ProductId(self::UUID_A), 0.95),
            new SearchResult(new ProductId(self::UUID_B), 0.80),
        ]);

        $this->productRepository
            ->method('findById')
            ->willReturnCallback(fn (ProductId $id) => match ($id->value()) {
                self::UUID_A => $productA,
                self::UUID_B => null,
            });

        $result = ($this->handler)(new SearchProductsQuery('running'));

        $this->assertCount(1, $result);
        $this->assertSame(self::UUID_A, $result[0]->id);
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

    private function make_product(string $uuid, string $name, string $description): Product
    {
        return Product::create(
            new ProductId($uuid),
            new ProductName($name),
            new ProductSemanticDescription($description),
        );
    }
}
