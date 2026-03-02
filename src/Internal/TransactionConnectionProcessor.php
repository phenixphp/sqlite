<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal;

use Amp\Future;
use Phenix\Sqlite\Internal\Tasks\ExecuteTransactionQuery;

class TransactionConnectionProcessor extends ConnectionProcessor
{
    /**
     * @return Future<SqliteConnectionResult>
     */
    public function query(string $query): Future
    {
        return $this->executeQuery(new ExecuteTransactionQuery($this->config, $query));
    }

    public function execute(string $sql, array $params = []): Future
    {
        return $this->executeQuery(new Tasks\ExecuteTransactionStatement($this->config, $sql, $params));
    }
}
