<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Http;

use App\Product\Application\SearchProducts\SearchProductsQuery;
use App\Product\Application\SearchProducts\SearchProductsResponse;
use App\Product\Infrastructure\Http\SearchProductsController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class SearchProductsControllerTest extends TestCase
{
    private MessageBusInterface&MockObject $queryBus;
    private SearchProductsController $controller;

    protected function setUp(): void
    {
        $this->queryBus   = $this->createMock(MessageBusInterface::class);
        $this->controller = new SearchProductsController($this->queryBus);
    }

    public function test_returns_200_with_results(): void
    {
        $results = [
            new SearchProductsResponse('550e8400-e29b-41d4-a716-446655440000', 'Shoes', 'Trail running shoes', 0.95),
        ];

        $envelope = new Envelope(new SearchProductsQuery('running'), [new HandledStamp($results, 'handler')]);

        $this->queryBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SearchProductsQuery::class))
            ->willReturn($envelope);

        $request  = Request::create('/products/search', 'GET', ['q' => 'running']);
        $response = ($this->controller)($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertCount(1, $body);
        $this->assertSame('Shoes', $body[0]['name']);
        $this->assertSame(0.95, $body[0]['score']);
    }

    public function test_returns_400_when_q_is_missing(): void
    {
        $this->queryBus->expects($this->never())->method('dispatch');

        $request  = Request::create('/products/search', 'GET');
        $response = ($this->controller)($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test_clamps_limit_to_maximum_50(): void
    {
        $envelope = new Envelope(new SearchProductsQuery('test', 50), [new HandledStamp([], 'handler')]);

        $this->queryBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                fn (SearchProductsQuery $q) => $q->limit === 50
            ))
            ->willReturn($envelope);

        $request = Request::create('/products/search', 'GET', ['q' => 'test', 'limit' => '200']);
        ($this->controller)($request);
    }

    public function test_uses_default_limit_of_10(): void
    {
        $envelope = new Envelope(new SearchProductsQuery('test', 10), [new HandledStamp([], 'handler')]);

        $this->queryBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                fn (SearchProductsQuery $q) => $q->limit === 10
            ))
            ->willReturn($envelope);

        $request = Request::create('/products/search', 'GET', ['q' => 'test']);
        ($this->controller)($request);
    }
}
