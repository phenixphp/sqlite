<?php declare(strict_types=1);

namespace Phenix\Sqlite\Contracts;

use Amp\Sql\SqlConnection;
use Phenix\Sqlite\SqliteConfig;

interface SqliteConnection extends SqliteLink, SqlConnection
{
    public function getConfig(): SqliteConfig;
}
