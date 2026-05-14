<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Cache;

use App\Product\Domain\Port\EmbeddingService;
use App\Product\Domain\ValueObject\Embedding;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use Psr\Cache\CacheItemPoolInterface;

final class CachedEmbeddingService implements EmbeddingService
{
    private const int TTL = 300; // 5 minutes

    public function __construct(
        private readonly EmbeddingService $inner,
        private readonly CacheItemPoolInterface $cache,
    ) {}

    public function generate(ProductSemanticDescription $description): Embedding
    {
        $key = 'embedding_'.md5($description->value());
        $item = $this->cache->getItem($key);

        if ($item->isHit()) {
            /** @var float[] $values */
            $values = $item->get();

            return new Embedding($values);
        }

        $embedding = $this->inner->generate($description);

        $item->set($embedding->values());
        $item->expiresAfter(self::TTL);
        $this->cache->save($item);

        return $embedding;
    }
}
