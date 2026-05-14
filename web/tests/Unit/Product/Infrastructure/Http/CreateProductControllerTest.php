<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Http;

use App\Product\Infrastructure\Http\CreateProductController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class CreateProductControllerTest extends TestCase
{
    private MessageBusInterface&MockObject $commandBus;
    private CreateProductController $controller;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(MessageBusInterface::class);
        $this->controller = new CreateProductController($this->commandBus);
    }

    public function testReturns201WithIdOnValidRequest(): void
    {
        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(fn (object $message) => new Envelope($message));

        $request = $this->buildRequest(['name' => 'Trail Shoes', 'semanticDescription' => 'Lightweight trail shoes']);

        $response = ($this->controller)($request);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertArrayHasKey('id', $body);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $body['id']
        );
    }

    public function testReturns400WhenNameIsMissing(): void
    {
        $this->commandBus->expects($this->never())->method('dispatch');

        $request = $this->buildRequest(['semanticDescription' => 'Some description']);

        self::assertSame(Response::HTTP_BAD_REQUEST, ($this->controller)($request)->getStatusCode());
    }

    public function testReturns400WhenSemanticDescriptionIsMissing(): void
    {
        $this->commandBus->expects($this->never())->method('dispatch');

        $request = $this->buildRequest(['name' => 'Some product']);

        self::assertSame(Response::HTTP_BAD_REQUEST, ($this->controller)($request)->getStatusCode());
    }

    private function buildRequest(array $body): Request
    {
        return new Request(content: json_encode($body));
    }
}
