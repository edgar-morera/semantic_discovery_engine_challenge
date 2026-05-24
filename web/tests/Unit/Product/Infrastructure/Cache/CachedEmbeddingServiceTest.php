<?php

declare(strict_types=1);

namespace App\Tests\Unit\Product\Infrastructure\Cache;

use App\Product\Domain\Port\EmbeddingService;
use App\Product\Domain\ValueObject\Embedding;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use App\Product\Infrastructure\Cache\CachedEmbeddingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class CachedEmbeddingServiceTest extends TestCase
{
    private EmbeddingService&MockObject $inner;
    private CacheItemPoolInterface&MockObject $cache;
    private CachedEmbeddingService $service;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(EmbeddingService::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->service = new CachedEmbeddingService($this->inner, $this->cache);
    }

    public function testCallsInnerAndCachesOnMiss(): void
    {
        $description = new ProductSemanticDescription('trail running shoes');
        $values = array_fill(0, Embedding::DIMENSIONS, 0.1);
        $embedding = new Embedding($values);

        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(false);
        $item->expects($this->once())->method('set')->with($values);
        $item->expects($this->once())->method('expiresAfter')->with(300);

        $expectedKey = 'embedding_'.md5($description->value());
        $this->cache->expects($this->once())->method('getItem')->with($expectedKey)->willReturn($item);
        $this->cache->expects($this->once())->method('save')->with($item);

        $this->inner->expects($this->once())->method('generate')->with($description)->willReturn($embedding);

        $result = $this->service->generate($description);

        $this->assertInstanceOf(Embedding::class, $result);
        $this->assertSame($values, $result->values());
    }

    public function testReturnsCachedEmbeddingWithoutCallingInnerOnHit(): void
    {
        $description = new ProductSemanticDescription('trail running shoes');
        $values = array_fill(0, Embedding::DIMENSIONS, 0.2);

        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(true);
        $item->method('get')->willReturn($values);

        $this->cache->method('getItem')->willReturn($item);
        $this->inner->expects($this->never())->method('generate');
        $this->cache->expects($this->never())->method('save');

        $result = $this->service->generate($description);

        $this->assertInstanceOf(Embedding::class, $result);
        $this->assertSame($values, $result->values());
    }

    public function testExceptionFromInnerServicePropagatesWithoutCaching(): void
    {
        $description = new ProductSemanticDescription('trail running shoes');

        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(false);

        $this->cache->method('getItem')->willReturn($item);
        $this->cache->expects($this->never())->method('save');

        $this->inner
            ->method('generate')
            ->willThrowException(new \RuntimeException('API unavailable'));

        $this->expectException(\RuntimeException::class);

        $this->service->generate($description);
    }

    public function testSameCacheKeyForSameDescription(): void
    {
        $desc = new ProductSemanticDescription('trail running shoes');
        $values = array_fill(0, Embedding::DIMENSIONS, 0.1);
        $embedding = new Embedding($values);

        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(false);
        $item->method('set')->willReturnSelf();
        $item->method('expiresAfter')->willReturnSelf();

        $capturedKey = null;
        $this->cache
            ->method('getItem')
            ->willReturnCallback(function (string $key) use ($item, &$capturedKey): CacheItemInterface {
                $capturedKey = $key;

                return $item;
            });
        $this->cache->method('save');
        $this->inner->method('generate')->willReturn($embedding);

        $this->service->generate($desc);
        $this->service->generate($desc);

        $this->assertSame('embedding_'.md5($desc->value()), $capturedKey);
    }
}
