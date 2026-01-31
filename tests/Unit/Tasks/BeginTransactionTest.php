<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
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
}
