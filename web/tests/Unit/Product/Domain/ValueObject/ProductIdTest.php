<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidProductIdException;
use App\Product\Domain\ValueObject\ProductId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductIdTest extends TestCase
{
    public function testCreatesValidUuidV4(): void
    {
        $id = new ProductId('550e8400-e29b-41d4-a716-446655440000');

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $id->value());
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $id1 = new ProductId('550e8400-e29b-41d4-a716-446655440000');
        $id2 = new ProductId('550e8400-e29b-41d4-a716-446655440000');

        $this->assertTrue($id1->equals($id2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $id1 = new ProductId('550e8400-e29b-41d4-a716-446655440000');
        $id2 = new ProductId('6ba7b810-9dad-41d1-80b4-00c04fd430c8');

        $this->assertFalse($id1->equals($id2));
    }

    public function testThrowsExceptionForEmptyString(): void
    {
        $this->expectException(InvalidProductIdException::class);

        new ProductId('');
    }

    public static function invalid_uuid_v4_provider(): array
    {
        return [
            'arbitrary string' => ['not-a-uuid'],
            'uuid v1' => ['550e8400-e29b-11d4-a716-446655440000'],
            'invalid variant' => ['550e8400-e29b-41d4-c716-446655440000'],
        ];
    }

    #[DataProvider('invalid_uuid_v4_provider')]
    public function testThrowsExceptionForInvalidUuidV4Format(string $value): void
    {
        $this->expectException(InvalidProductIdException::class);

        new ProductId($value);
    }
}
