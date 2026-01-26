<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Throwable;
use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\SqliteConfig;
use Phenix\Sqlite\Internal\ConnectionContext;

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
            $dbPath = $this->config->getPath();

            // Get persistent PDO connection from ConnectionContext
            $pdo = ConnectionContext::getConnection($dbPath);

            // Check if already in transaction
            if ($pdo->inTransaction()) {
                return Result::failure(null, sprintf(
                    'Already in transaction (PDO->inTransaction() = true, db = %s)',
                    $dbPath
                ));
            }

            // Begin transaction
            // Note: PDO->beginTransaction() doesn't support transaction type parameters
            // All SQLite transaction types (DEFERRED, IMMEDIATE, EXCLUSIVE) start with BEGIN
            // The transactionType parameter is informational for now
            $pdo->beginTransaction();

            // Mark transaction state in ConnectionContext
            ConnectionContext::markTransactionActive($dbPath);

            return Result::success(['started' => true]);
        } catch (Throwable $e) {
            return Result::failure(null, $e->getMessage() . ' [DB: ' . ($dbPath ?? 'unknown') . ']');
        }
    }
}
