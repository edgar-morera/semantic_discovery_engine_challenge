<?php

declare(strict_types=1);

namespace App\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidProductSemanticDescriptionException;

final class ProductSemanticDescription
{
    public function __construct(private readonly string $value)
    {
        if (trim($value) === '') {
            throw new InvalidProductSemanticDescriptionException('Semantic description cannot be empty.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
