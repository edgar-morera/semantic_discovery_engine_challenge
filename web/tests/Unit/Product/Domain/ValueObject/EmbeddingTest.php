<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidEmbeddingException;
use App\Product\Domain\ValueObject\Embedding;
use PHPUnit\Framework\TestCase;

final class EmbeddingTest extends TestCase
{
    public function testCreatesValidEmbedding(): void
    {
        $embedding = new Embedding([0.1, 0.2, 0.3]);

        $this->assertSame([0.1, 0.2, 0.3], $embedding->values());
    }

    public function testConvertsNumericStringsToFloat(): void
    {
        $embedding = new Embedding(['0.25', '0.5']);

        $this->assertSame(0.25, $embedding->values()[0]);
        $this->assertSame(0.5, $embedding->values()[1]);
    }

    public function testConvertsIntegersToFloat(): void
    {
        $embedding = new Embedding([1, 2]);

        $this->assertSame(1.0, $embedding->values()[0]);
    }

    public function testEqualsReturnsTrueForSameValues(): void
    {
        $values = [0.1, 0.2, 0.3];

        $this->assertTrue((new Embedding($values))->equals(new Embedding($values)));
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        $this->assertFalse((new Embedding([0.1]))->equals(new Embedding([0.2])));
    }

    public function testThrowsForNonNumericElement(): void
    {
        $this->expectException(InvalidEmbeddingException::class);

        new Embedding([0.1, 'not-a-number', 0.3]);
    }

    public function testThrowsForNanElement(): void
    {
        $this->expectException(InvalidEmbeddingException::class);

        new Embedding([0.1, NAN, 0.3]);
    }

    public function testThrowsForInfiniteElement(): void
    {
        $this->expectException(InvalidEmbeddingException::class);

        new Embedding([0.1, INF, 0.3]);
    }
}
