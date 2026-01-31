<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\Internal\Tasks\ConnectDatabase;
use Phenix\Sqlite\SqliteConfig;
use Tests\TestCase;

class ConnectDatabaseTest extends TestCase
{
    /**
     * @test
     */
    public function it_opens_sqlite_connection(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());
        $task = new ConnectDatabase($config);

        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->isSuccess());
    }
}
