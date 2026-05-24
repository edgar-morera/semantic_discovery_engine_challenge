<?php

declare(strict_types=1);

namespace App\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidProductNameException;

final class ProductName
{
    private const MAX_LENGTH = 255;

    private readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if ('' === $trimmed) {
            throw new InvalidProductNameException('Product name cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new InvalidProductNameException(sprintf('Product name cannot exceed %d characters.', self::MAX_LENGTH));
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
