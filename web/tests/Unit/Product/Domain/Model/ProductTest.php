<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\Model;

use App\Product\Domain\Model\Product;
use App\Product\Domain\ValueObject\Embedding;
use App\Product\Domain\ValueObject\ProductId;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    private const string VALID_UUID = '550e8400-e29b-41d4-a716-446655440000';

    public function test_creates_product_with_factory_method(): void
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

    public function test_is_not_indexed_after_creation(): void
    {
        $product = $this->make_product();

        $this->assertFalse($product->isIndexed());
        $this->assertNull($product->embedding());
    }

    public function test_is_indexed_after_assigning_embedding(): void
    {
        $product = $this->make_product();
        $embedding = new Embedding(array_fill(0, Embedding::DIMENSIONS, 0.1));

        $product->assignEmbedding($embedding);

        $this->assertTrue($product->isIndexed());
        $this->assertSame($embedding, $product->embedding());
    }

    public function test_assign_embedding_replaces_previous_one(): void
    {
        $product = $this->make_product();
        $first = new Embedding(array_fill(0, Embedding::DIMENSIONS, 0.1));
        $second = new Embedding(array_fill(0, Embedding::DIMENSIONS, 0.9));

        $product->assignEmbedding($first);
        $product->assignEmbedding($second);

        $this->assertSame($second, $product->embedding());
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
