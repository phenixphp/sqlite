<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use PDO;

class TransactionTask extends ConnectDatabase
{
    protected function connect(): PDO
    {
        return $GLOBALS['sqlite_connection'] ??= parent::connect();
    }

    protected function closeConnection(): void
    {
        $GLOBALS['sqlite_connection'] = null;
    }
}
