<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Http;

use App\Product\Application\SearchProducts\SearchProductsQuery;
use App\Product\Application\SearchProducts\SearchProductsResponse;
use App\Product\Domain\Exception\InvalidProductSemanticDescriptionException;
use App\Product\Domain\Exception\InvalidSearchLimitException;
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
        $this->queryBus = $this->createMock(MessageBusInterface::class);
        $this->controller = new SearchProductsController($this->queryBus);
    }

    public function testReturns200WithResults(): void
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

        $request = Request::create('/products/search', 'GET', ['q' => 'running']);
        $response = ($this->controller)($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertCount(1, $body);
        $this->assertSame('Shoes', $body[0]['name']);
        $this->assertSame(0.95, $body[0]['score']);
    }

    public function testReturns400WhenQIsMissing(): void
    {
        $this->queryBus->expects($this->never())->method('dispatch');

        $request = Request::create('/products/search', 'GET');
        $response = ($this->controller)($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testUsesDefaultLimitOf10(): void
    {
        $envelope = new Envelope(new SearchProductsQuery('test', 10), [new HandledStamp([], 'handler')]);

        $this->queryBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                fn (SearchProductsQuery $q) => 10 === $q->limit
            ))
            ->willReturn($envelope);

        $request = Request::create('/products/search', 'GET', ['q' => 'test']);
        ($this->controller)($request);
    }

    public function testReturns400WhenQIsEmptyString(): void
    {
        $this->queryBus->expects($this->never())->method('dispatch');

        $request = Request::create('/products/search', 'GET', ['q' => '']);
        $response = ($this->controller)($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testReturns400WithMessageWhenQIsWhitespaceOnly(): void
    {
        $this->queryBus
            ->method('dispatch')
            ->willThrowException(new InvalidProductSemanticDescriptionException('Semantic description cannot be empty.'));

        $request = Request::create('/products/search', 'GET', ['q' => '   ']);
        $response = ($this->controller)($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('Semantic description cannot be empty.', json_decode($response->getContent(), true)['error']);
    }

    public function testReturns400WithMessageWhenLimitExceedsMaximum(): void
    {
        $this->queryBus
            ->method('dispatch')
            ->willThrowException(new InvalidSearchLimitException('Search limit must be between 1 and 50, 200 given.'));

        $request = Request::create('/products/search', 'GET', ['q' => 'test', 'limit' => '200']);
        $response = ($this->controller)($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('Search limit must be between 1 and 50, 200 given.', json_decode($response->getContent(), true)['error']);
    }

    public function testReturns400WithMessageWhenLimitIsNegative(): void
    {
        $this->queryBus
            ->method('dispatch')
            ->willThrowException(new InvalidSearchLimitException('Search limit must be between 1 and 50, -5 given.'));

        $request = Request::create('/products/search', 'GET', ['q' => 'test', 'limit' => '-5']);
        $response = ($this->controller)($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testAcceptsLimitAtBoundaries(): void
    {
        $envelopeLower = new Envelope(new SearchProductsQuery('test', 1), [new HandledStamp([], 'handler')]);
        $envelopeUpper = new Envelope(new SearchProductsQuery('test', 50), [new HandledStamp([], 'handler')]);

        $this->queryBus
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnOnConsecutiveCalls($envelopeLower, $envelopeUpper);

        ($this->controller)(Request::create('/products/search', 'GET', ['q' => 'test', 'limit' => '1']));
        ($this->controller)(Request::create('/products/search', 'GET', ['q' => 'test', 'limit' => '50']));
    }
}
