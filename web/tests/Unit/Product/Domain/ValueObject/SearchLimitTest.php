<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidSearchLimitException;
use App\Product\Domain\ValueObject\SearchLimit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SearchLimitTest extends TestCase
{
    public function testAcceptsLowerBoundary(): void
    {
        $this->assertSame(1, (new SearchLimit(1))->value());
    }

    public function testAcceptsUpperBoundary(): void
    {
        $this->assertSame(50, (new SearchLimit(50))->value());
    }

    public function testAcceptsValueInRange(): void
    {
        $this->assertSame(25, (new SearchLimit(25))->value());
    }

    public function testDefaultReturns10(): void
    {
        $this->assertSame(10, SearchLimit::default()->value());
    }

    public static function out_of_range_provider(): array
    {
        return [
            'zero'     => [0],
            'negative' => [-1],
            'above max' => [51],
            'far above' => [100],
        ];
    }

    #[DataProvider('out_of_range_provider')]
    public function testThrowsExceptionForOutOfRangeValue(int $value): void
    {
        $this->expectException(InvalidSearchLimitException::class);

        new SearchLimit($value);
    }
}
