<?php

declare(strict_types=1);

namespace App\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidProductSemanticDescriptionException;

final class ProductSemanticDescription
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if ('' === $trimmed) {
            throw new InvalidProductSemanticDescriptionException('Semantic description cannot be empty.');
        }

        $this->value = $trimmed;
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
