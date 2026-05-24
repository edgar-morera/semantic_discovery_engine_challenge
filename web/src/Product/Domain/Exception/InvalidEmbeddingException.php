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
}
