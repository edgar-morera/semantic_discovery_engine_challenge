<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Application\IndexProduct;

use App\Product\Application\IndexProduct\IndexProductCommand;
use App\Product\Application\IndexProduct\IndexProductCommandHandler;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Model\Product;
use App\Product\Domain\Port\EmbeddingService;
use App\Product\Domain\Port\ProductSearchPort;
use App\Product\Domain\Repository\ProductRepository;
use App\Product\Domain\ValueObject\Embedding;
use App\Product\Domain\ValueObject\ProductId;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class IndexProductCommandHandlerTest extends TestCase
{
    private const string VALID_UUID = '550e8400-e29b-41d4-a716-446655440000';

    private ProductRepository&MockObject $productRepository;
    private EmbeddingService&MockObject $embeddingService;
    private ProductSearchPort&MockObject $productSearchRepository;
    private IndexProductCommandHandler $handler;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->embeddingService = $this->createMock(EmbeddingService::class);
        $this->productSearchRepository = $this->createMock(ProductSearchPort::class);

        $this->handler = new IndexProductCommandHandler(
            $this->productRepository,
            $this->embeddingService,
            $this->productSearchRepository,
        );
    }

    public function testIndexesProductWhenItExists(): void
    {
        $product = $this->make_product();
        $embedding = new Embedding(array_fill(0, Embedding::DIMENSIONS, 0.1));

        $this->productRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->isInstanceOf(ProductId::class))
            ->willReturn($product);

        $this->embeddingService
            ->expects($this->once())
            ->method('generate')
            ->willReturn($embedding);

        $this->productSearchRepository
            ->expects($this->once())
            ->method('index')
            ->with($product, $embedding);

        ($this->handler)(new IndexProductCommand(self::VALID_UUID));
    }

    public function testAssignsEmbeddingToProductAfterIndexing(): void
    {
        $product = $this->make_product();
        $embedding = new Embedding(array_fill(0, Embedding::DIMENSIONS, 0.5));

        $this->productRepository->method('findById')->willReturn($product);
        $this->embeddingService->method('generate')->willReturn($embedding);
        $this->productSearchRepository->method('index');

        ($this->handler)(new IndexProductCommand(self::VALID_UUID));

        $this->assertTrue($product->isIndexed());
        $this->assertSame($embedding, $product->embedding());
    }

    public function testThrowsProductNotFoundWhenProductDoesNotExist(): void
    {
        $this->productRepository
            ->method('findById')
            ->willReturn(null);

        $this->embeddingService->expects($this->never())->method('generate');
        $this->productSearchRepository->expects($this->never())->method('index');

        $this->expectException(ProductNotFoundException::class);

        ($this->handler)(new IndexProductCommand(self::VALID_UUID));
    }

    private function make_product(): Product
    {
        return Product::create(
            new ProductId(self::VALID_UUID),
            new ProductName('Test Product'),
            new ProductSemanticDescription('A test description'),
        );
    }
}
