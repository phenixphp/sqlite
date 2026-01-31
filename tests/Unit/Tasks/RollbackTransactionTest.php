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
use Phenix\Sqlite\Internal\Tasks\RollbackTransaction;

class RollbackTransactionTest extends TestCase
{
    /**
     * @test
     */
    public function it_rolls_back_transaction(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $begin = new BeginTransaction($config);
        $begin->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $task = new RollbackTransaction($config);
        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->output()['rolledback'] ?? false);
    }

    /**
     * @test
     */
    public function it_returns_failure_when_when_exception_is_thrown(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $begin = new BeginTransaction($config);
        $begin->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $task = new class ($config) extends RollbackTransaction {
            protected function connect(): PDO
            {
                throw new Exception('Error rolling back transaction');
            }
        };

        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('Error rolling back transaction', $result->message() ?? '');
    }

    /**
     * @test
     */
    public function it_returns_failure_when_no_transaction_active(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $task = new RollbackTransaction($config);
        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('No active transaction to rollback', $result->message() ?? '');
    }
}
