<?php

declare(strict_types=1);

namespace App\Product\Domain\Exception;

final class ProductNotFoundException extends \DomainException
{
    public function __construct(string $productId)
    {
        parent::__construct(sprintf('Product with ID "%s" not found.', $productId));
    }
}
