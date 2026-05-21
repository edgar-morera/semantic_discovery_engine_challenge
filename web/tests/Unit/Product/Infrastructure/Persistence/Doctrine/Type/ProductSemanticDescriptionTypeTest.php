<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Persistence\Doctrine\Type;

use App\Product\Domain\ValueObject\ProductSemanticDescription;
use App\Product\Infrastructure\Persistence\Doctrine\Type\ProductSemanticDescriptionType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;

final class ProductSemanticDescriptionTypeTest extends TestCase
{
    private ProductSemanticDescriptionType $type;
    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        $this->type = new ProductSemanticDescriptionType();
        $this->platform = $this->createMock(AbstractPlatform::class);
    }

    public function testGetNameReturnsProductSemanticDescription(): void
    {
        $this->assertSame('product_semantic_description', $this->type->getName());
    }

    public function testConvertsToPHPValueReturnsProductSemanticDescription(): void
    {
        $result = $this->type->convertToPHPValue('Lightweight trail running shoes', $this->platform);

        $this->assertInstanceOf(ProductSemanticDescription::class, $result);
        $this->assertSame('Lightweight trail running shoes', $result->value());
    }

    public function testConvertsToPHPValueReturnsNullForNull(): void
    {
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testConvertsToDatabaseValueFromProductSemanticDescription(): void
    {
        $desc = new ProductSemanticDescription('Lightweight trail running shoes');

        $this->assertSame('Lightweight trail running shoes', $this->type->convertToDatabaseValue($desc, $this->platform));
    }

    public function testConvertsToDatabaseValueReturnsNullForNull(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }
}
