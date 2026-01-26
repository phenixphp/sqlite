<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\SqliteConfig;
use Phenix\Sqlite\Internal\ConnectionContext;
use Throwable;

class RollbackTransaction extends ConnectDatabase
{
    public function __construct(SqliteConfig $config)
    {
        parent::__construct($config);
    }

    public function run(Channel $channel, Cancellation $cancellation): Result
    {
        try {
            $dbPath = $this->config->getPath();

            // Get persistent PDO connection from ConnectionContext
            $pdo = ConnectionContext::getConnection($dbPath);

            if (!$pdo->inTransaction()) {
                return Result::failure(null, sprintf(
                    'No active transaction to rollback (PDO->inTransaction() = false, db = %s)',
                    $dbPath
                ));
            }

            $pdo->rollBack();

            // Clear transaction state in ConnectionContext
            ConnectionContext::markTransactionInactive($dbPath);

            return Result::success(['rolledback' => true]);
        } catch (Throwable $e) {
            return Result::failure(null, $e->getMessage() . ' [DB: ' . ($dbPath ?? 'unknown') . ']');
        }
    }
}
