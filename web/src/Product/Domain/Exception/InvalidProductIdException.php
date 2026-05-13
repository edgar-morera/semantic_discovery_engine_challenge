<?php

declare(strict_types=1);

namespace App\Product\Domain\Exception;

final class InvalidProductIdException extends \DomainException
{
    public function __construct(string $value)
    {
        parent::__construct(sprintf('"%s" is not a valid product ID (UUID v4 expected).', $value));
    }
}
