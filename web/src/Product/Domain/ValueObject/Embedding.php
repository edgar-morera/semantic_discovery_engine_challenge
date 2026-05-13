<?php

declare(strict_types=1);

namespace App\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidEmbeddingException;

final class Embedding
{
    public const int DIMENSIONS = 384;

    /** @var float[] */
    private readonly array $values;

    /** @param array<int, mixed> $values */
    public function __construct(array $values)
    {
        if (count($values) !== self::DIMENSIONS) {
            throw InvalidEmbeddingException::wrongDimensions(count($values));
        }

        foreach ($values as $index => $element) {
            if (!is_numeric($element)) {
                throw InvalidEmbeddingException::nonNumericElement($index);
            }
        }

        $this->values = array_map(fn (mixed $v): float => (float) $v, $values);
    }

    /** @return float[] */
    public function values(): array
    {
        return $this->values;
    }

    public function equals(self $other): bool
    {
        return $this->values === $other->values;
    }
}
