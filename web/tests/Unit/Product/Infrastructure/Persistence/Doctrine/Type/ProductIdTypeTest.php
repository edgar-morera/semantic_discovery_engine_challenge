<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Persistence\Doctrine\Type;

use App\Product\Domain\ValueObject\ProductId;
use App\Product\Infrastructure\Persistence\Doctrine\Type\ProductIdType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;

final class ProductIdTypeTest extends TestCase
{
    private const string VALID_UUID = '550e8400-e29b-41d4-a716-446655440000';

    private ProductIdType $type;
    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        $this->type = new ProductIdType();
        $this->platform = $this->createMock(AbstractPlatform::class);
    }

    public function testGetNameReturnsProductId(): void
    {
        $this->assertSame('product_id', $this->type->getName());
    }

    public function testConvertsToPHPValueReturnsProductId(): void
    {
        $result = $this->type->convertToPHPValue(self::VALID_UUID, $this->platform);

        $this->assertInstanceOf(ProductId::class, $result);
        $this->assertSame(self::VALID_UUID, $result->value());
    }

    public function testConvertsToPHPValueReturnsNullForNull(): void
    {
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testConvertsToDatabaseValueFromProductId(): void
    {
        $id = new ProductId(self::VALID_UUID);

        $this->assertSame(self::VALID_UUID, $this->type->convertToDatabaseValue($id, $this->platform));
    }

    public function testConvertsToDatabaseValueReturnsNullForNull(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }
}
