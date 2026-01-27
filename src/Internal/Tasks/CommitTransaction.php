<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Throwable;

class CommitTransaction extends TransactionTask
{
    public function run(Channel $channel, Cancellation $cancellation): Result
    {
        try {
            $dbPath = $this->config->getPath();

            $pdo = $this->connect();

            if (! $pdo->inTransaction()) {
                return Result::failure(null, sprintf(
                    'No active transaction to commit (db = %s)',
                    $dbPath
                ));
            }

            $pdo->commit();

            $this->closeConnection();

            return Result::success(['committed' => true]);
        } catch (Throwable $e) {
            return Result::failure(null, $e->getMessage() . ' [DB: ' . ($dbPath ?? 'unknown') . ']');
        }
    }
}
