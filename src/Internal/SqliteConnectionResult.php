<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal;

use Phenix\Sqlite\Contracts\SqliteResult;
use Phenix\Sqlite\SqliteColumnDefinition;
use Traversable;

class SqliteConnectionResult implements SqliteResult
{
    /**
     * @param array<SqliteColumnDefinition>|null $columnDefinitions
     * @param list<array<string, mixed>> $rows
     */
    public function __construct(
        private readonly array|null $columnDefinitions = null,
        private array $rows = [],
        private readonly int|null $lastInsertId = null,
        private readonly int $affectedRows = 0,
    ) {
    }

    public function fetchRow(): array|null
    {
        return array_shift($this->rows);
    }

    public function getIterator(): Traversable
    {
        while (($row = $this->fetchRow()) !== null) {
            yield $row;
        }
    }

    public function getLastInsertId(): int|null
    {
        return $this->lastInsertId;
    }

    public function getRowCount(): int|null
    {
        return $this->affectedRows;
    }

    public function getColumnDefinitions(): array|null
    {
        return $this->columnDefinitions;
    }

    public function getColumnCount(): int|null
    {
        return $this->columnDefinitions !== null ? count($this->columnDefinitions) : null;
    }

    public function getNextResult(): self|null
    {
        return null;
    }
}
