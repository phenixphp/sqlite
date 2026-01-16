<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Contracts;

use Amp\Sql\SqlTransaction;

interface SqliteTransaction extends SqliteLink, SqlTransaction
{
}
