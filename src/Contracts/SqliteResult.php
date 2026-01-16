<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Contracts;

use Amp\Sql\SqlResult;
use Phenix\Sqlite\SqliteColumnDefinition;

interface SqliteResult extends SqlResult
{
    public function getNextResult(): self|null;

    public function getLastInsertId(): int|null;

    /**
     * @return array<SqliteColumnDefinition>|null
     */
    public function getColumnDefinitions(): array|null;
}
