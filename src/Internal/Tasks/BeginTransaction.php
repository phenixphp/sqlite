<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Throwable;

class BeginTransaction extends TransactionTask
{
    public function run(Channel $channel, Cancellation $cancellation): Result
    {
        try {
            $dbPath = $this->config->getPath();

            $pdo = $this->connect();

            if ($pdo->inTransaction()) {
                return Result::failure(null, sprintf(
                    'Already in transaction (PDO->inTransaction() = true, db = %s)',
                    $dbPath
                ));
            }

            $pdo->beginTransaction();

            return Result::success(['started' => true]);
        } catch (Throwable $e) {
            return Result::failure(null, $e->getMessage() . ' [DB: ' . ($dbPath ?? 'unknown') . ']');
        }
    }
}
