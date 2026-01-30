<?php

declare(strict_types=1);

namespace Tests\Unit;

use Amp\Sql\SqlConfig;
use Phenix\Sqlite\SqliteConnector;
use Tests\TestCase;
use TypeError;

class ConnectorTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_error_when_config_is_not_sqlite_config(): void
    {
        $this->expectException(TypeError::class);

        $mockConfig = $this->createMock(SqlConfig::class);

        SqliteConnector::make($mockConfig);
    }
}
