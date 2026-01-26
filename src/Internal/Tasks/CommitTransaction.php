<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\SqliteConfig;
use Throwable;

class CommitTransaction extends ConnectDatabase
{
    public function __construct(SqliteConfig $config)
    {
        parent::__construct($config);
    }

    public function run(Channel $channel, Cancellation $cancellation): Result
    {
        try {
            $getConnection = $GLOBALS['getConnection'] ?? null;

            if ($getConnection === null) {
                return Result::failure(null, 'Worker bootstrap not loaded - persistent connections unavailable');
            }

            $dbPath = $this->config->getPath();
            $pdo = $getConnection($dbPath);

            if (! $pdo->inTransaction()) {
                return Result::failure(null, sprintf(
                    'No active transaction to commit (db = %s)',
                    $dbPath
                ));
            }

            $pdo->commit();

            // Clear transaction state
            $setTransactionState = $GLOBALS['setTransactionState'] ?? null;
            if ($setTransactionState !== null) {
                $setTransactionState($dbPath, false);
            }

            return Result::success(['committed' => true]);
        } catch (Throwable $e) {
            return Result::failure(null, $e->getMessage() . ' [DB: ' . ($dbPath ?? 'unknown') . ']');
        }
    }
}
