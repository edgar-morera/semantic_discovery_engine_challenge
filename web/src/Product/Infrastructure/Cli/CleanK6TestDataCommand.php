<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Cli;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:k6:clean', description: 'Delete all products created by k6 tests from MySQL and Qdrant')]
final class CleanK6TestDataCommand extends Command
{
    private const string K6_PREFIX = '[k6-test]';
    private const string COLLECTION = 'products';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        private readonly string $qdrantDsn,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ids = $this->entityManager->getConnection()->fetchFirstColumn(
            'SELECT id FROM products WHERE name LIKE :prefix',
            ['prefix' => self::K6_PREFIX.'%'],
        );

        if (empty($ids)) {
            $output->writeln('No k6 test data found — nothing to clean.');

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Found <info>%d</info> k6 test product(s). Cleaning up...', count($ids)));

        $this->httpClient->request(
            'POST',
            $this->qdrantDsn.'/collections/'.self::COLLECTION.'/points/delete',
            [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => ['points' => $ids],
            ],
        );

        $output->writeln('  ✓ Deleted from Qdrant');

        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM products WHERE name LIKE :prefix',
            ['prefix' => self::K6_PREFIX.'%'],
        );

        $output->writeln('  ✓ Deleted from MySQL');
        $output->writeln(sprintf('<info>Done.</info> %d product(s) removed.', count($ids)));

        return Command::SUCCESS;
    }
}
