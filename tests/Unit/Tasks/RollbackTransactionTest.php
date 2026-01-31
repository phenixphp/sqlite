<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\Internal\Tasks\BeginTransaction;
use Phenix\Sqlite\Internal\Tasks\RollbackTransaction;
use Phenix\Sqlite\SqliteConfig;
use Tests\TestCase;

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
}
