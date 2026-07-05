<?php

declare(strict_types=1);

namespace App\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidSearchLimitException;

final class SearchLimit
{
    public const int MIN = 1;
    public const int MAX = 50;
    public const int DEFAULT_VALUE = 10;

    public function __construct(private readonly int $value)
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw new InvalidSearchLimitException(sprintf('Search limit must be between %d and %d, %d given.', self::MIN, self::MAX, $value));
        }
    }

    public static function default(): self
    {
        return new self(self::DEFAULT_VALUE);
    }

    public function value(): int
    {
        return $this->value;
    }
}
