<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks;

use PDO;
use Exception;
use Tests\TestCase;
use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\SqliteConfig;
use Phenix\Sqlite\Internal\Tasks\BeginTransaction;
use Phenix\Sqlite\Internal\Tasks\CommitTransaction;

class CommitTransactionTest extends TestCase
{
    /**
     * @test
     */
    public function it_commits_transaction(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $beginTask = new BeginTransaction($config);
        $beginTask->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $task = new CommitTransaction($config);
        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->output()['committed'] ?? false);
    }

    /**
     * @test
     */
    public function it_returns_failure_when_when_exception_is_thrown(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $beginTask = new BeginTransaction($config);
        $beginTask->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $task = new class($config) extends CommitTransaction {
            protected function connect(): PDO
            {
                throw new Exception('Error committing transaction');
            }
        };

        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('Error committing transaction', $result->message() ?? '');
    }

    /**
     * @test
     */
    public function it_returns_failure_when_no_transaction_is_active(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $task = new CommitTransaction($config);
        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('No active transaction to commit', $result->message() ?? '');
    }
}
