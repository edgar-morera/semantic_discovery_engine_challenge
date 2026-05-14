<?php

declare(strict_types=1);

namespace App\Product\Application\CreateProduct;

use App\Product\Domain\Model\Product;
use App\Product\Domain\Repository\ProductRepository;
use App\Product\Domain\ValueObject\ProductId;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateProductCommandHandler
{
    public function __construct(private readonly ProductRepository $repository)
    {
    }

    public function __invoke(CreateProductCommand $command): void
    {
        $product = Product::create(
            new ProductId($command->id),
            new ProductName($command->name),
            new ProductSemanticDescription($command->semanticDescription),
        );

        $this->repository->save($product);
    }
}
