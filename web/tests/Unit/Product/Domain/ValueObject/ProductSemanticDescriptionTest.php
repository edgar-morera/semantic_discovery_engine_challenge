<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidProductSemanticDescriptionException;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductSemanticDescriptionTest extends TestCase
{
    public function testCreatesValidDescription(): void
    {
        $description = new ProductSemanticDescription('Red thermal cycling jersey for cold weather');

        $this->assertSame('Red thermal cycling jersey for cold weather', $description->value());
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $d1 = new ProductSemanticDescription('Warm gloves');
        $d2 = new ProductSemanticDescription('Warm gloves');

        $this->assertTrue($d1->equals($d2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $d1 = new ProductSemanticDescription('Warm gloves');
        $d2 = new ProductSemanticDescription('Lightweight helmet');

        $this->assertFalse($d1->equals($d2));
    }

    public static function blank_description_provider(): array
    {
        return [
            'empty string' => [''],
            'whitespace only' => ['     '],
        ];
    }

    #[DataProvider('blank_description_provider')]
    public function testThrowsExceptionForBlankDescription(string $value): void
    {
        $this->expectException(InvalidProductSemanticDescriptionException::class);

        new ProductSemanticDescription($value);
    }

    public function testTrimsLeadingAndTrailingWhitespace(): void
    {
        $description = new ProductSemanticDescription('  Warm gloves  ');

        $this->assertSame('Warm gloves', $description->value());
    }
}
