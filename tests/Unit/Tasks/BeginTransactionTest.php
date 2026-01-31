<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use PDO;
use Phenix\Sqlite\Internal\Exceptions\SqliteException;
use Phenix\Sqlite\Internal\Tasks\BeginTransaction;
use Phenix\Sqlite\SqliteConfig;
use Tests\TestCase;

class BeginTransactionTest extends TestCase
{
    /**
     * @test
     */
    public function it_begins_transaction(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());
        $task = new BeginTransaction($config);

        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->output()['started'] ?? false);
    }

    /**
     * @test
     */
    public function it_returns_failure_when_when_exception_is_thrown(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $task = new class ($config) extends BeginTransaction {
            protected function connect(): PDO
            {
                throw new SqliteException('Error beginning transaction');
            }
        };

        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('Error beginning transaction', $result->message() ?? '');
    }
}
