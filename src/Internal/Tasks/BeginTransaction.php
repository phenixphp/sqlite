<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\SqliteConfig;
use Throwable;

class BeginTransaction extends ConnectDatabase
{
    public function __construct(
        SqliteConfig $config,
        private readonly string $transactionType
    ) {
        parent::__construct($config);
    }

    public function run(Channel $channel, Cancellation $cancellation): Result
    {
        try {
            $getConnection = $GLOBALS['getConnection'] ?? null;

            if ($getConnection === null) {
                return Result::failure(null, 'Worker bootstrap not loaded - persistent connections unavailable');
            }

            // Set the database path for this and subsequent tasks
            $dbPath = $this->config->getPath();
            $GLOBALS['current_db_path'] = $dbPath;

            $pdo = $getConnection($dbPath);

            // Check if already in transaction
            if ($pdo->inTransaction()) {
                return Result::failure(null, sprintf(
                    'Already in transaction (PDO->inTransaction() = true, db = %s)',
                    $dbPath
                ));
            }

            // Begin transaction with the specified type
            // Note: PDO->beginTransaction() doesn't support transaction type parameters in SQLite
            // We must use PDO's beginTransaction() for proper inTransaction() tracking
            // All SQLite transaction types (DEFERRED, IMMEDIATE, EXCLUSIVE) start the same way
            // The differences are in locking behavior which SQLite handles internally

            $pdo->beginTransaction();

            // Mark transaction state
            $setTransactionState = $GLOBALS['setTransactionState'] ?? null;
            if ($setTransactionState !== null) {
                $setTransactionState($dbPath, true);
            }

            return Result::success(['started' => true]);
        } catch (Throwable $e) {
            return Result::failure(null, $e->getMessage() . ' [DB: ' . ($dbPath ?? 'unknown') . ']');
        }
    }
}
