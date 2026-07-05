<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\External;

use App\Product\Domain\Exception\InvalidEmbeddingException;
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
        $this->service = new HuggingFaceEmbeddingService(
            $this->httpClient,
            'test-api-key',
            'https://router.huggingface.co/hf-inference/models',
            'test-model',
            384,
        );
    }

    public function testReturnsEmbeddingFromApiResponse(): void
    {
        $vector = array_fill(0, 384, 0.1);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($vector);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://router.huggingface.co/hf-inference/models/test-model',
                $this->arrayHasKey('json'),
            )
            ->willReturn($response);

        $description = new ProductSemanticDescription('Trail running shoes for mountain races');
        $embedding = $this->service->generate($description);

        $this->assertInstanceOf(Embedding::class, $embedding);
        $this->assertSame($vector, $embedding->values());
    }

    public function testSendsAuthorizationHeaderWithApiKey(): void
    {
        $vector = array_fill(0, 384, 0.0);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn($vector);

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

    public function testThrowsInvalidEmbeddingExceptionOnUnexpectedResponseFormat(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([]);

        $this->httpClient->method('request')->willReturn($response);

        $this->expectException(InvalidEmbeddingException::class);

        $this->service->generate(new ProductSemanticDescription('some description'));
    }

    public function testThrowsInvalidEmbeddingExceptionWhenFirstElementIsNotFloat(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([1, 2, 3]);

        $this->httpClient->method('request')->willReturn($response);

        $this->expectException(InvalidEmbeddingException::class);

        $this->service->generate(new ProductSemanticDescription('some description'));
    }

    public function testThrowsInvalidEmbeddingExceptionOnWrongDimensionCount(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(array_fill(0, 128, 0.1));

        $this->httpClient->method('request')->willReturn($response);

        $this->expectException(InvalidEmbeddingException::class);
        $this->expectExceptionMessage('Expected 384 dimensions');

        $this->service->generate(new ProductSemanticDescription('some description'));
    }
}
