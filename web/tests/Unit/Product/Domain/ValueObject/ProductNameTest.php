<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidProductNameException;
use App\Product\Domain\ValueObject\ProductName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductNameTest extends TestCase
{
    public function testCreatesValidName(): void
    {
        $name = new ProductName('Running shoes ultra pro');

        $this->assertSame('Running shoes ultra pro', $name->value());
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $name1 = new ProductName('Running shoes');
        $name2 = new ProductName('Running shoes');

        $this->assertTrue($name1->equals($name2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $name1 = new ProductName('Running shoes');
        $name2 = new ProductName('Cycling jersey');

        $this->assertFalse($name1->equals($name2));
    }

    public static function blank_name_provider(): array
    {
        return [
            'empty string' => [''],
            'whitespace only' => ['   '],
        ];
    }

    #[DataProvider('blank_name_provider')]
    public function testThrowsExceptionForBlankName(string $value): void
    {
        $this->expectException(InvalidProductNameException::class);

        new ProductName($value);
    }

    public function testThrowsExceptionWhenExceeds255Characters(): void
    {
        $this->expectException(InvalidProductNameException::class);

        new ProductName(str_repeat('a', 256));
    }

    public function testAcceptsNameOfExactly255Characters(): void
    {
        $name = new ProductName(str_repeat('a', 255));

        $this->assertSame(255, mb_strlen($name->value()));
    }
}
