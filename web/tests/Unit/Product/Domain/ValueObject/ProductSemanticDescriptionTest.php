<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidProductSemanticDescriptionException;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductSemanticDescriptionTest extends TestCase
{
    public function test_creates_valid_description(): void
    {
        $description = new ProductSemanticDescription('Red thermal cycling jersey for cold weather');

        $this->assertSame('Red thermal cycling jersey for cold weather', $description->value());
    }

    public function test_equals_returns_true_for_same_value(): void
    {
        $d1 = new ProductSemanticDescription('Warm gloves');
        $d2 = new ProductSemanticDescription('Warm gloves');

        $this->assertTrue($d1->equals($d2));
    }

    public function test_equals_returns_false_for_different_value(): void
    {
        $d1 = new ProductSemanticDescription('Warm gloves');
        $d2 = new ProductSemanticDescription('Lightweight helmet');

        $this->assertFalse($d1->equals($d2));
    }

    public static function blank_description_provider(): array
    {
        return [
            'empty string'    => [''],
            'whitespace only' => ['     '],
        ];
    }

    #[DataProvider('blank_description_provider')]
    public function test_throws_exception_for_blank_description(string $value): void
    {
        $this->expectException(InvalidProductSemanticDescriptionException::class);

        new ProductSemanticDescription($value);
    }
}
