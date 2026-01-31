<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\Internal\Tasks\BeginTransaction;
use Phenix\Sqlite\Internal\Tasks\PrepareTransactionStatement;
use Phenix\Sqlite\SqliteConfig;
use Tests\TestCase;

class PrepareTransactionStatementTest extends TestCase
{
    /**
     * @test
     */
    public function it_prepares_transaction_statement(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $create = new PrepareTransactionStatement($config, 'CREATE TABLE test (id INTEGER PRIMARY KEY)');
        $create->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $begin = new BeginTransaction($config);
        $begin->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $task = new PrepareTransactionStatement($config, 'SELECT * FROM test');
        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->isSuccess());
    }
}
