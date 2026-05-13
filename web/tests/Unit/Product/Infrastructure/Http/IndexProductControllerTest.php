<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Http;

use App\Product\Application\IndexProduct\IndexProductCommand;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Infrastructure\Http\IndexProductController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class IndexProductControllerTest extends TestCase
{
    private const string VALID_UUID = '550e8400-e29b-41d4-a716-446655440000';

    private MessageBusInterface&MockObject $commandBus;
    private IndexProductController $controller;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(MessageBusInterface::class);
        $this->controller = new IndexProductController($this->commandBus);
    }

    public function test_returns_204_when_product_is_indexed(): void
    {
        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(IndexProductCommand::class))
            ->willReturn(new Envelope(new IndexProductCommand(self::VALID_UUID)));

        $response = ($this->controller)(self::VALID_UUID);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function test_returns_404_when_product_not_found(): void
    {
        $this->commandBus
            ->method('dispatch')
            ->willThrowException(new ProductNotFoundException(self::VALID_UUID));

        $response = ($this->controller)(self::VALID_UUID);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
