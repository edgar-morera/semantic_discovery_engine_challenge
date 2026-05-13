<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\External;

use App\Product\Domain\ValueObject\Embedding;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use App\Product\Infrastructure\External\HuggingFaceEmbeddingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class HuggingFaceEmbeddingServiceTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private HuggingFaceEmbeddingService $service;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->service    = new HuggingFaceEmbeddingService($this->httpClient, 'test-api-key');
    }

    public function test_returns_embedding_from_api_response(): void
    {
        $vector   = array_fill(0, Embedding::DIMENSIONS, 0.1);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([$vector]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->stringContains('granite-embedding-97m-multilingual'),
                $this->arrayHasKey('json'),
            )
            ->willReturn($response);

        $description = new ProductSemanticDescription('Trail running shoes for mountain races');
        $embedding   = $this->service->generate($description);

        $this->assertInstanceOf(Embedding::class, $embedding);
        $this->assertCount(Embedding::DIMENSIONS, $embedding->values());
    }

    public function test_sends_authorization_header_with_api_key(): void
    {
        $vector   = array_fill(0, Embedding::DIMENSIONS, 0.0);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([$vector]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(static function (array $options): bool {
                    return isset($options['headers']['Authorization'])
                        && str_starts_with($options['headers']['Authorization'], 'Bearer ');
                }),
            )
            ->willReturn($response);

        $this->service->generate(new ProductSemanticDescription('some description'));
    }

    public function test_throws_runtime_exception_on_unexpected_response_format(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([]);

        $this->httpClient->method('request')->willReturn($response);

        $this->expectException(\RuntimeException::class);

        $this->service->generate(new ProductSemanticDescription('some description'));
    }
}
