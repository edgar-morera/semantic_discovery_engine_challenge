<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Cli;

use App\Product\Domain\ValueObject\Embedding;
use App\Product\Infrastructure\Cli\SetupQdrantCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class SetupQdrantCommandTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $command = new SetupQdrantCommand($this->httpClient, 'http://qdrant:6333');
        $this->tester = new CommandTester($command);
    }

    public function testSkipsCreationWhenCollectionAlreadyExists(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://qdrant:6333/collections/products')
            ->willReturn($response);

        $exitCode = $this->tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('already exists', $this->tester->getDisplay());
    }

    public function testCreatesCollectionWithCorrectPayloadWhenItDoesNotExist(): void
    {
        $getResponse = $this->createMock(ResponseInterface::class);
        $getResponse->method('getStatusCode')->willReturn(404);

        $putResponse = $this->createMock(ResponseInterface::class);

        $this->httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(
                function (string $method, string $url, array $options = []) use ($getResponse, $putResponse): ResponseInterface {
                    if ('GET' === $method) {
                        return $getResponse;
                    }

                    $this->assertSame('PUT', $method);
                    $this->assertSame('http://qdrant:6333/collections/products', $url);
                    $this->assertSame(Embedding::DIMENSIONS, $options['json']['vectors']['size']);
                    $this->assertSame('Cosine', $options['json']['vectors']['distance']);

                    return $putResponse;
                },
            );

        $exitCode = $this->tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('created', $this->tester->getDisplay());
    }
}
