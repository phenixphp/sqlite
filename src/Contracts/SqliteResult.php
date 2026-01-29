<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Contracts;

use Amp\Sql\SqlResult;
use IteratorAggregate;
use Phenix\Sqlite\SqliteColumnDefinition;

interface SqliteResult extends SqlResult, IteratorAggregate
{
    public function getNextResult(): self|null;

    public function getLastInsertId(): int|null;

    /**
     * @return array<SqliteColumnDefinition>|null
     */
    public function getColumnDefinitions(): array|null;
}
