<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal;

use Amp\Parallel\Worker\Worker;

/**
 * Factory for creating and managing SQLite workers.
 * Provides access to a shared worker pool for queries and dedicated workers for transactions.
 */
class SqliteWorkerFactory
{
    private static SqliteWorkerPool|null $pool = null;

    /**
     * Get the shared worker pool instance.
     */
    public static function getPool(): SqliteWorkerPool
    {
        if (self::$pool === null) {
            self::$pool = new SqliteWorkerPool(
                bootstrapPath: __DIR__ . DIRECTORY_SEPARATOR . 'sqlite-worker.php'
            );
        }

        return self::$pool;
    }

    /**
     * Acquire a worker from the pool for general queries.
     * Worker should be released back using release() when done.
     */
    public static function acquire(): Worker
    {
        return self::getPool()->acquire();
    }

    /**
     * Release a worker back to the pool.
     */
    public static function release(Worker $worker): void
    {
        self::getPool()->release($worker);
    }

    /**
     * Acquire a dedicated worker for transactions.
     * This worker won't be shared with other operations.
     */
    public static function acquireDedicated(): Worker
    {
        return self::getPool()->acquireDedicated();
    }

    /**
     * Release a dedicated worker after transaction completes.
     */
    public static function releaseDedicated(Worker $worker): void
    {
        self::getPool()->releaseDedicated($worker);
    }

    /**
     * Get pool statistics.
     *
     * @return array{total: int, available: int, busy: int}
     */
    public static function getStats(): array
    {
        return self::getPool()->getStats();
    }

    /**
     * Shutdown the worker pool.
     */
    public static function shutdown(): void
    {
        if (self::$pool !== null) {
            self::$pool->shutdown();
            self::$pool = null;
        }
    }

    /**
     * Legacy method for backward compatibility.
     * @deprecated Use acquire() instead
     */
    public static function create(): Worker
    {
        return self::acquire();
    }
}

