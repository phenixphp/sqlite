<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use PDOException;
use Phenix\Sqlite\SqliteConfig;

class ExecuteTransactionStatement extends TransactionTask
{
    /**
     * @param SqliteConfig $config
     * @param string $sql
     * @param array<mixed> $params
     */
    public function __construct(
        SqliteConfig $config,
        protected string $sql,
        protected array $params,
    ) {
        parent::__construct($config);
    }

    public function run(Channel $channel, Cancellation $cancellation): Result
    {
        try {
            $pdo = $this->connect();

            if (! $pdo->inTransaction()) {
                return Result::failure(message: "Not in a transaction, cannot execute prepared statement: {$this->sql}");
            }

            return $this->execute($pdo, $this->sql, $this->params);
        } catch (PDOException $e) {
            return Result::failure(message: $e->getMessage());
        }
    }
}
