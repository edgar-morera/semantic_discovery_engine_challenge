<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Cli;

use App\Product\Domain\ValueObject\Embedding;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:qdrant:setup', description: 'Create Qdrant collection if it does not exist')]
final class SetupQdrantCommand extends Command
{
    private const string COLLECTION = 'products';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $qdrantDsn,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $url = $this->qdrantDsn.'/collections/'.self::COLLECTION;

        $statusCode = $this->httpClient->request('GET', $url)->getStatusCode();

        if (200 === $statusCode) {
            $output->writeln('Collection <info>'.self::COLLECTION.'</info> already exists — skipping.');

            return Command::SUCCESS;
        }

        $this->httpClient->request('PUT', $url, [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'vectors' => [
                    'size' => Embedding::DIMENSIONS,
                    'distance' => 'Cosine',
                ],
            ],
        ]);

        $output->writeln('Collection <info>'.self::COLLECTION.'</info> created.');

        return Command::SUCCESS;
    }
}
