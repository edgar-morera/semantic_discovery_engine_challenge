<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\External;

use App\Product\Domain\Port\EmbeddingService;
use App\Product\Domain\ValueObject\Embedding;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HuggingFaceEmbeddingService implements EmbeddingService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $apiUrl,
        private readonly string $model,
        private readonly int $dimensions,
    ) {
    }

    public function generate(ProductSemanticDescription $description): Embedding
    {
        $response = $this->httpClient->request('POST', $this->apiUrl.'/'.$this->model, [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'inputs' => $description->value(),
            ],
        ]);

        $body = $response->toArray();

        if (!isset($body[0]) || !is_float($body[0])) {
            throw new \RuntimeException('Unexpected response format from HuggingFace API.');
        }

        if ($this->dimensions !== count($body)) {
            throw new \RuntimeException(sprintf(
                'Expected %d dimensions from HuggingFace API, got %d.',
                $this->dimensions,
                count($body),
            ));
        }

        /** @var float[] $body */
        return new Embedding($body);
    }
}
