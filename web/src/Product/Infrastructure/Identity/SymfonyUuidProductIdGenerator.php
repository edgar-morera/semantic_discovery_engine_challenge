<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Identity;

use App\Product\Domain\Port\ProductIdGenerator;
use App\Product\Domain\ValueObject\ProductId;
use Symfony\Component\Uid\Uuid;

final class SymfonyUuidProductIdGenerator implements ProductIdGenerator
{
    public function generate(): ProductId
    {
        return new ProductId(Uuid::v4()->toRfc4122());
    }
}
