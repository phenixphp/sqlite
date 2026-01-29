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
                return Result::failure(null, 'Not in a transaction - cannot execute  prepared statement|');
            }

            $stmt = $pdo->prepare($this->sql);

            if (! $stmt) {
                return Result::failure(message: "Failed to prepare statement: {$this->sql}");
            }

            $parameterCount = $this->countParameters($this->sql);

            $columnDefinitions = $this->buildColumnDefinitions($stmt);

            return Result::success([
                'parameterCount' => $parameterCount,
                'columnDefinitions' => $columnDefinitions,
            ]);
        } catch (Throwable $e) {
            return Result::failure(null, $e->getMessage());
        }
    }
}
