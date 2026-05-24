<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Http;

use App\Product\Domain\Exception\InvalidProductNameException;
use App\Product\Domain\Exception\InvalidProductSemanticDescriptionException;
use App\Product\Domain\Port\ProductIdGenerator;
use App\Product\Domain\ValueObject\ProductId;
use App\Product\Infrastructure\Http\CreateProductController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class CreateProductControllerTest extends TestCase
{
    private const string FIXED_UUID = '550e8400-e29b-41d4-a716-446655440000';

    private MessageBusInterface&MockObject $commandBus;
    private ProductIdGenerator&MockObject $idGenerator;
    private CreateProductController $controller;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(MessageBusInterface::class);
        $this->idGenerator = $this->createMock(ProductIdGenerator::class);
        $this->controller = new CreateProductController($this->commandBus, $this->idGenerator);
    }

    public function testReturns201WithIdOnValidRequest(): void
    {
        $this->idGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn(new ProductId(self::FIXED_UUID));

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(fn (object $message) => new Envelope($message));

        $response = ($this->controller)($this->buildRequest(['name' => 'Trail Shoes', 'semanticDescription' => 'Lightweight trail shoes']));

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertSame(self::FIXED_UUID, $body['id']);
    }

    public function testReturns400WhenNameIsMissing(): void
    {
        $this->idGenerator->expects($this->never())->method('generate');
        $this->commandBus->expects($this->never())->method('dispatch');

        self::assertSame(Response::HTTP_BAD_REQUEST, ($this->controller)($this->buildRequest(['semanticDescription' => 'Some description']))->getStatusCode());
    }

    public function testReturns400WhenSemanticDescriptionIsMissing(): void
    {
        $this->idGenerator->expects($this->never())->method('generate');
        $this->commandBus->expects($this->never())->method('dispatch');

        self::assertSame(Response::HTTP_BAD_REQUEST, ($this->controller)($this->buildRequest(['name' => 'Some product']))->getStatusCode());
    }

    public function testReturns400WhenBodyIsEmpty(): void
    {
        $this->idGenerator->expects($this->never())->method('generate');
        $this->commandBus->expects($this->never())->method('dispatch');

        $request = new Request(content: '');
        self::assertSame(Response::HTTP_BAD_REQUEST, ($this->controller)($request)->getStatusCode());
    }

    public function testReturns400WithMessageWhenNameIsBlank(): void
    {
        $this->idGenerator
            ->method('generate')
            ->willReturn(new ProductId(self::FIXED_UUID));

        $this->commandBus
            ->method('dispatch')
            ->willThrowException(new InvalidProductNameException('Product name cannot be empty.'));

        $response = ($this->controller)($this->buildRequest(['name' => '   ', 'semanticDescription' => 'Valid description']));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Product name cannot be empty.', json_decode($response->getContent(), true)['error']);
    }

    public function testReturns400WithMessageWhenSemanticDescriptionIsBlank(): void
    {
        $this->idGenerator
            ->method('generate')
            ->willReturn(new ProductId(self::FIXED_UUID));

        $this->commandBus
            ->method('dispatch')
            ->willThrowException(new InvalidProductSemanticDescriptionException('Semantic description cannot be empty.'));

        $response = ($this->controller)($this->buildRequest(['name' => 'Valid name', 'semanticDescription' => '   ']));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Semantic description cannot be empty.', json_decode($response->getContent(), true)['error']);
    }

    private function buildRequest(array $body): Request
    {
        return new Request(content: json_encode($body));
    }
}
