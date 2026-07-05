<?php

declare(strict_types=1);

namespace App\Product\Domain\Exception;

final class InvalidEmbeddingException extends \DomainException
{
    public static function nonNumericElement(int $index): self
    {
        return new self(sprintf(
            'Embedding element at index %d is not numeric.',
            $index,
        ));
    }

    public static function unexpectedResponseFormat(): self
    {
        return new self('Unexpected response format from embedding provider.');
    }

    public static function dimensionMismatch(int $expected, int $actual): self
    {
        return new self(sprintf(
            'Expected %d dimensions from embedding provider, got %d.',
            $expected,
            $actual,
        ));
    }
}
