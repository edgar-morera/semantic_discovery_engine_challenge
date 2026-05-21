<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\Model;

use App\Product\Domain\Model\Product;
use App\Product\Domain\ValueObject\ProductId;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    private const string VALID_UUID = '550e8400-e29b-41d4-a716-446655440000';

    public function testCreatesProductWithFactoryMethod(): void
    {
        $product = Product::create(
            new ProductId(self::VALID_UUID),
            new ProductName('Thermal cycling jersey'),
            new ProductSemanticDescription('Red jersey for cold weather cycling'),
        );

        $this->assertSame(self::VALID_UUID, $product->productId()->value());
        $this->assertSame('Thermal cycling jersey', $product->productName()->value());
        $this->assertSame('Red jersey for cold weather cycling', $product->semanticDescription()->value());
    }
}
