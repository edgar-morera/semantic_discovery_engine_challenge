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
    public function test_creates_product_with_factory_method(): void
    {
        $product = Product::create(
            new ProductId('550e8400-e29b-41d4-a716-446655440000'),
            new ProductName('Thermal cycling jersey'),
            new ProductSemanticDescription('Red jersey for cold weather cycling'),
        );

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $product->productId()->value());
        $this->assertSame('Thermal cycling jersey', $product->productName()->value());
        $this->assertSame('Red jersey for cold weather cycling', $product->semanticDescription()->value());
    }


}
