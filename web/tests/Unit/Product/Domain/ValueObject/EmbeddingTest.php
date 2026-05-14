<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidEmbeddingException;
use App\Product\Domain\ValueObject\Embedding;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmbeddingTest extends TestCase
{
    public function testCreatesValidEmbeddingWithExactDimensions(): void
    {
        $values = array_fill(0, Embedding::DIMENSIONS, 0.5);

        $embedding = new Embedding($values);

        $this->assertCount(Embedding::DIMENSIONS, $embedding->values());
    }

    public function testConvertsNumericStringsToFloat(): void
    {
        $values = array_fill(0, Embedding::DIMENSIONS, '0.25');

        $embedding = new Embedding($values);

        $this->assertSame(0.25, $embedding->values()[0]);
    }

    public function testConvertsIntegersToFloat(): void
    {
        $values = array_fill(0, Embedding::DIMENSIONS, 1);

        $embedding = new Embedding($values);

        $this->assertSame(1.0, $embedding->values()[0]);
    }

    public function testEqualsReturnsTrueForSameValues(): void
    {
        $values = array_fill(0, Embedding::DIMENSIONS, 0.1);

        $this->assertTrue((new Embedding($values))->equals(new Embedding($values)));
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        $a = array_fill(0, Embedding::DIMENSIONS, 0.1);
        $b = array_fill(0, Embedding::DIMENSIONS, 0.2);

        $this->assertFalse((new Embedding($a))->equals(new Embedding($b)));
    }

    public static function invalid_dimension_provider(): array
    {
        return [
            'empty array' => [[]],
            'too few (383)' => [array_fill(0, 383, 0.1)],
            'too many (385)' => [array_fill(0, 385, 0.1)],
        ];
    }

    #[DataProvider('invalid_dimension_provider')]
    public function testThrowsForWrongNumberOfDimensions(array $values): void
    {
        $this->expectException(InvalidEmbeddingException::class);

        new Embedding($values);
    }

    public function testThrowsForNonNumericElement(): void
    {
        $values = array_fill(0, Embedding::DIMENSIONS, 0.1);
        $values[10] = 'not-a-number';

        $this->expectException(InvalidEmbeddingException::class);

        new Embedding($values);
    }

    public function testDimensionsConstantIs384(): void
    {
        $this->assertSame(384, Embedding::DIMENSIONS);
    }
}
