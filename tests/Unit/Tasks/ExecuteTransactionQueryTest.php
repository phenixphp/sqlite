<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\Internal\Tasks\BeginTransaction;
use Phenix\Sqlite\Internal\Tasks\ExecuteQuery;
use Phenix\Sqlite\Internal\Tasks\ExecuteTransactionQuery;
use Phenix\Sqlite\SqliteConfig;
use Tests\TestCase;

class ExecuteTransactionQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_executes_transaction_query(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $create = new ExecuteQuery($config, 'CREATE TABLE test (id INTEGER PRIMARY KEY)');
        $create->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $begin = new BeginTransaction($config);
        $begin->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $task = new ExecuteTransactionQuery($config, 'SELECT * FROM test');
        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->isSuccess());
        $this->assertIsArray($result->output()['rows'] ?? null);
    }

    /**
     * @test
     */
    public function it_returns_failure_on_invalid_query(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $begin = new BeginTransaction($config);
        $begin->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $task = new ExecuteTransactionQuery($config, 'INVALID QUERY');
        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertFalse($result->isSuccess());
    }

    /**
     * @test
     */
    public function it_fails_when_no_transaction_active(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $task = new ExecuteTransactionQuery($config, 'SELECT * FROM test');
        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertFalse($result->isSuccess());
    }
}
