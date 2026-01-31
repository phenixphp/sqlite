<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\Internal\Tasks\ExecuteQuery;
use Phenix\Sqlite\SqliteConfig;
use Tests\TestCase;

class ExecuteQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_runs_query(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());
        $task = new ExecuteQuery($config, 'CREATE TABLE test (id INTEGER PRIMARY KEY)');

        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        /** @var array<string, mixed> */
        $data = $result->output();

        $this->assertTrue($result->isSuccess());
        $this->assertNull($data['lastInsertId']);
        $this->assertCount(0, $data['rows']);

    }
}
