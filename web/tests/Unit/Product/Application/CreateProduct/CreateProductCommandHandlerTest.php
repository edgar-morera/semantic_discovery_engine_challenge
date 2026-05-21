<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Application\CreateProduct;

use App\Product\Application\CreateProduct\CreateProductCommand;
use App\Product\Application\CreateProduct\CreateProductCommandHandler;
use App\Product\Domain\Model\Product;
use App\Product\Domain\Repository\ProductRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateProductCommandHandlerTest extends TestCase
{
    private ProductRepository&MockObject $repository;
    private CreateProductCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductRepository::class);
        $this->handler = new CreateProductCommandHandler($this->repository);
    }

    public function testSavesProductWhenCommandIsValid(): void
    {
        $command = new CreateProductCommand(
            id: '550e8400-e29b-41d4-a716-446655440000',
            name: 'Running shoes X1',
            semanticDescription: 'Lightweight trail running shoes with carbon plate',
        );

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Product::class));

        ($this->handler)($command);
    }
}
