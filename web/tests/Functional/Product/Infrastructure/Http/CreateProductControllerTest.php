<?php

declare(strict_types=1);

namespace App\Tests\Functional\Product\Infrastructure\Http;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CreateProductControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->executeStatement('DELETE FROM products');

        parent::tearDown();
    }

    public function test_returns_201_with_product_id_on_valid_request(): void
    {
        $client = static::createClient();

        $client->request(
            method: 'POST',
            uri: '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name'                => 'Trail Running Shoes X200',
                'semanticDescription' => 'Lightweight carbon plate trail running shoes for mountain races',
            ]),
        );

        $response = $client->getResponse();
        $body     = json_decode($response->getContent(), true);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertArrayHasKey('id', $body);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $body['id']
        );
    }

    public function test_returns_400_when_name_is_missing(): void
    {
        $client = static::createClient();

        $client->request(
            method: 'POST',
            uri: '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['semanticDescription' => 'Some description']),
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
    }

    public function test_returns_400_when_semantic_description_is_missing(): void
    {
        $client = static::createClient();

        $client->request(
            method: 'POST',
            uri: '/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'Some product']),
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
    }
}
