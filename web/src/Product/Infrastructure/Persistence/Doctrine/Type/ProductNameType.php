<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Persistence\Doctrine\Type;

use App\Product\Domain\ValueObject\ProductName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class ProductNameType extends StringType
{
    public const NAME = 'product_name';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ProductName
    {
        if (null === $value) {
            return null;
        }

        return new ProductName((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof ProductName ? $value->value() : (string) $value;
    }
}
