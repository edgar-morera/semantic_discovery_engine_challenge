<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidProductNameException;
use App\Product\Domain\ValueObject\ProductName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductNameTest extends TestCase
{
    public function test_creates_valid_name(): void
    {
        $name = new ProductName('Running shoes ultra pro');

        $this->assertSame('Running shoes ultra pro', $name->value());
    }

    public function test_equals_returns_true_for_same_value(): void
    {
        $name1 = new ProductName('Running shoes');
        $name2 = new ProductName('Running shoes');

        $this->assertTrue($name1->equals($name2));
    }

    public function test_equals_returns_false_for_different_value(): void
    {
        $name1 = new ProductName('Running shoes');
        $name2 = new ProductName('Cycling jersey');

        $this->assertFalse($name1->equals($name2));
    }

    public static function blank_name_provider(): array
    {
        return [
            'empty string'    => [''],
            'whitespace only' => ['   '],
        ];
    }

    #[DataProvider('blank_name_provider')]
    public function test_throws_exception_for_blank_name(string $value): void
    {
        $this->expectException(InvalidProductNameException::class);

        new ProductName($value);
    }

    public function test_throws_exception_when_exceeds_255_characters(): void
    {
        $this->expectException(InvalidProductNameException::class);

        new ProductName(str_repeat('a', 256));
    }

    public function test_accepts_name_of_exactly_255_characters(): void
    {
        $name = new ProductName(str_repeat('a', 255));

        $this->assertSame(255, mb_strlen($name->value()));
    }
}
