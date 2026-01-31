<?php

declare(strict_types=1);

namespace Tests\Unit\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\Internal\Tasks\PrepareStatement;
use Phenix\Sqlite\SqliteConfig;
use Tests\TestCase;

class PrepareStatementTest extends TestCase
{
    /**
     * @test
     */
    public function it_prepares_statement(): void
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        $task = new PrepareStatement($config, 'SELECT 1');
        $result = $task->run($this->createMock(Channel::class), $this->createMock(Cancellation::class));

        $this->assertTrue($result->isSuccess());
    }
}
