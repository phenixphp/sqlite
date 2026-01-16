<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Contracts;

use Amp\Sql\SqlStatement;
use Error;
use Phenix\Sqlite\SqliteColumnDefinition;

interface SqliteStatement extends SqlStatement
{
    public function execute(array $params = []): SqliteResult;

    /**
     * @throws Error If $paramId does not exist.
     */
    public function bind(int|string $paramId, string $data): void;

    /**
     * @return array<SqliteColumnDefinition>
     */
    public function getColumnDefinitions(): array;

    /**
     * @return array<SqliteColumnDefinition>
     */
    public function getParameterDefinitions(): array;

    public function reset(): void;
}
