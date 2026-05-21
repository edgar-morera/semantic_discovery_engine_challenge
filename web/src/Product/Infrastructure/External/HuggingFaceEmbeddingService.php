<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\External;

use App\Product\Domain\Port\EmbeddingService;
use App\Product\Domain\ValueObject\Embedding;
use App\Product\Domain\ValueObject\ProductSemanticDescription;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HuggingFaceEmbeddingService implements EmbeddingService
{
    private const string API_URL = 'https://router.huggingface.co/hf-inference/models/ibm-granite/granite-embedding-97m-multilingual-r2';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
    ) {
    }

    public function generate(ProductSemanticDescription $description): Embedding
    {
        $response = $this->httpClient->request('POST', self::API_URL, [
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

        /** @var float[] $body */
        return new Embedding($body);
    }
}
