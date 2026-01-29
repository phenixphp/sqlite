<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\SqliteConfig;
use Throwable;

class ExecuteTransactionQuery extends TransactionTask
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
                return Result::failure(null, 'Not in a transaction - cannot execute transaction query');
            }

            return $this->query($pdo, $this->sql);
        } catch (Throwable $e) {
            return Result::failure(null, $e->getMessage());
        }
    }
}
