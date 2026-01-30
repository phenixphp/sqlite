<?php

declare(strict_types=1);

namespace Phenix\Sqlite;

use Amp\Cancellation;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Sql\SqlConfig;
use Amp\Sql\SqlConnector;
use TypeError;

use function sprintf;

class SqliteConnector implements SqlConnector
{
    use ForbidCloning;
    use ForbidSerialization;

    public static function make(SqlConfig $config, null|Cancellation $cancellation = null): SqliteConnection
    {
        return (new self())->connect($config, $cancellation);
    }

    public function connect(SqlConfig $config, null|Cancellation $cancellation = null): SqliteConnection
    {
        if (! $config instanceof SqliteConfig) {
            throw new TypeError(sprintf('Must provide an instance of %s to SQLite connectors', SqliteConfig::class));
        }

        return SqliteConnection::connect($config, $cancellation);
    }
}
