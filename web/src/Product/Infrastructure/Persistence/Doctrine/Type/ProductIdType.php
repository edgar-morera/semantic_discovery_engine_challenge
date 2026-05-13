<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Persistence\Doctrine\Type;

use App\Product\Domain\ValueObject\ProductId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;

final class ProductIdType extends GuidType
{
    public const NAME = 'product_id';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ProductId
    {
        if (null === $value) {
            return null;
        }

        return new ProductId((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof ProductId ? $value->value() : (string) $value;
    }
}
