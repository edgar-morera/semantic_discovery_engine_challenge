<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Persistence\Doctrine\Type;

use App\Product\Domain\ValueObject\ProductName;
use App\Product\Infrastructure\Persistence\Doctrine\Type\ProductNameType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;

final class ProductNameTypeTest extends TestCase
{
    private ProductNameType $type;
    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        $this->type = new ProductNameType();
        $this->platform = $this->createMock(AbstractPlatform::class);
    }

    public function testGetNameReturnsProductName(): void
    {
        $this->assertSame('product_name', $this->type->getName());
    }

    public function testConvertsToPHPValueReturnsProductName(): void
    {
        $result = $this->type->convertToPHPValue('Trail Running Shoes', $this->platform);

        $this->assertInstanceOf(ProductName::class, $result);
        $this->assertSame('Trail Running Shoes', $result->value());
    }

    public function testConvertsToPHPValueReturnsNullForNull(): void
    {
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testConvertsToDatabaseValueFromProductName(): void
    {
        $name = new ProductName('Trail Running Shoes');

        $this->assertSame('Trail Running Shoes', $this->type->convertToDatabaseValue($name, $this->platform));
    }

    public function testConvertsToDatabaseValueReturnsNullForNull(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }
}
