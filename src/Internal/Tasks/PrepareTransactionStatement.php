<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\SqliteConfig;
use Throwable;

class PrepareTransactionStatement extends TransactionTask
{
    public function __construct(
        SqliteConfig $config,
        private readonly string $sql
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

            return $this->prepare($pdo, $this->sql);
        } catch (Throwable $e) {
            return Result::failure(message: $e->getMessage());
        }
    }
}
