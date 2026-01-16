<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Contracts;

use Amp\Sql\SqlLink;

interface SqliteLink extends SqliteExecutor, SqlLink
{
    public function beginTransaction(): SqliteTransaction;
}
