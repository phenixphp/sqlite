<?php declare(strict_types=1);

namespace Phenix\Sqlite;

use TypeError;
use Amp\Cancellation;
use Amp\ForbidCloning;
use Amp\Sql\SqlConfig;
use Amp\Sql\SqlConnector;
use Amp\ForbidSerialization;
use Phenix\Sqlite\SqliteConfig;

use function sprintf;

class SqliteConnector implements SqlConnector
{
    use ForbidCloning;
    use ForbidSerialization;

    public function connect(SqlConfig $config, ?Cancellation $cancellation = null): SqliteConnection
    {
        if (!$config instanceof SqliteConfig) {
            throw new TypeError(sprintf('Must provide an instance of %s to SQLite connectors', SqliteConfig::class));
        }

        return SqliteConnection::connect($config, $cancellation);
    }
}
