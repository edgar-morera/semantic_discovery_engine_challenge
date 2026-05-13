<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Persistence\Doctrine\Type;

use App\Product\Domain\ValueObject\ProductSemanticDescription;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

final class ProductSemanticDescriptionType extends TextType
{
    public const NAME = 'product_semantic_description';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ProductSemanticDescription
    {
        if (null === $value) {
            return null;
        }

        return new ProductSemanticDescription((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof ProductSemanticDescription ? $value->value() : (string) $value;
    }
}
