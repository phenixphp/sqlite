<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks;

use Tests\TestCase;
use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\SqliteConfig;
use Phenix\Sqlite\Internal\Tasks\ExecuteQuery;
use Phenix\Sqlite\Internal\Tasks\BeginTransaction;
use Phenix\Sqlite\Internal\Tasks\ExecuteTransactionStatement;

class ExecuteTransactionStatementTest extends TestCase
{
    /**
     * @test
     */
    public function it_executes_transaction_statement(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $create = new ExecuteQuery($config, 'CREATE TABLE test (id INTEGER PRIMARY KEY)', []);
        $create->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $begin = new BeginTransaction($config);
        $begin->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $task = new ExecuteTransactionStatement($config, 'INSERT INTO test (id) VALUES (1)', []);
        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->isSuccess());
    }
}
