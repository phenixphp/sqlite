<?php declare(strict_types=1);

namespace Phenix\Sqlite\Contracts;

use Amp\Sql\SqlExecutor;

interface SqliteExecutor extends SqlExecutor
{
    public function query(string $sql): SqliteResult;

    public function prepare(string $sql): SqliteStatement;

    public function execute(string $sql, array $params = []): SqliteResult;
}
