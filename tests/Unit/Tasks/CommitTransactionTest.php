<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\Internal\Tasks\BeginTransaction;
use Phenix\Sqlite\Internal\Tasks\CommitTransaction;
use Phenix\Sqlite\SqliteConfig;
use Tests\TestCase;

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
}
