<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Throwable;

class RollbackTransaction extends TransactionTask
{
    public function run(Channel $channel, Cancellation $cancellation): Result
    {
        try {
            $dbPath = $this->config->getPath();

            $pdo = $this->connect();

            if (! $pdo->inTransaction()) {
                return Result::failure(null, sprintf(
                    'No active transaction to rollback (PDO->inTransaction() = false, db = %s)',
                    $dbPath
                ));
            }

            $pdo->rollBack();

            $this->closeConnection();

            return Result::success(['rolledback' => true]);
        } catch (Throwable $e) {
            $this->closeConnection();

            return Result::failure(null, $e->getMessage() . ' [DB: ' . ($dbPath ?? 'unknown') . ']');
        }
    }
}
