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
        $this->service = new HuggingFaceEmbeddingService($this->httpClient, 'test-api-key');
    }

    public function testReturnsEmbeddingFromApiResponse(): void
    {
        $vector = array_fill(0, Embedding::DIMENSIONS, 0.1);
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
        $embedding = $this->service->generate($description);

        $this->assertInstanceOf(Embedding::class, $embedding);
        $this->assertCount(Embedding::DIMENSIONS, $embedding->values());
    }

    public function testSendsAuthorizationHeaderWithApiKey(): void
    {
        $vector = array_fill(0, Embedding::DIMENSIONS, 0.0);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([$vector]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(static function (array $options): bool {
                    return 'Bearer test-api-key' === ($options['headers']['Authorization'] ?? null);
                }),
            )
            ->willReturn($response);

        $this->service->generate(new ProductSemanticDescription('some description'));
    }

    public function testReturnsEmbeddingFromFlatResponseFormat(): void
    {
        $vector = array_fill(0, Embedding::DIMENSIONS, 0.1);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($vector);

        $this->httpClient->method('request')->willReturn($response);

        $embedding = $this->service->generate(new ProductSemanticDescription('trail running shoes'));

        $this->assertInstanceOf(Embedding::class, $embedding);
        $this->assertCount(Embedding::DIMENSIONS, $embedding->values());
    }

    public function testThrowsRuntimeExceptionOnUnexpectedResponseFormat(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([]);

        $this->httpClient->method('request')->willReturn($response);

        $this->expectException(\RuntimeException::class);

        $this->service->generate(new ProductSemanticDescription('some description'));
    }
}
