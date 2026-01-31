<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\Internal\Tasks\ExecuteStatement;
use Phenix\Sqlite\SqliteConfig;
use Tests\TestCase;

class ExecuteStatementTest extends TestCase
{
    /**
     * @test
     */
    public function it_executes_statement(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());
        $create = new ExecuteStatement($config, 'CREATE TABLE test (id INTEGER PRIMARY KEY)', []);

        $create->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $task = new ExecuteStatement($config, 'INSERT INTO test (id) VALUES (1)', []);
        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->isSuccess());
    }

    /**
     * @test
     */
    public function it_fails_on_invalid_statement(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $task = new ExecuteStatement($config, 'INVALID STATEMENT', []);
        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertFalse($result->isSuccess());
    }
}
