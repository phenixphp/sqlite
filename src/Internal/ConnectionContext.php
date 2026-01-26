<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal;

use PDO;

/**
 * Manages persistent PDO connections and transaction state within a worker process.
 * This class uses static properties to maintain state across multiple task executions
 * in the same worker, replacing the previous $GLOBALS approach.
 */
class ConnectionContext
{
    /** @var array<string, PDO> */
    private static array $pdoConnections = [];

    /** @var array<string, bool> */
    private static array $transactionStates = [];

    /** @var array<string, bool> */
    private static array $pragmasApplied = [];

    /**
     * Get or create a persistent PDO connection for the given database path.
     *
     * @param string $path Database file path
     * @param array<string, mixed> $options PDO options
     * @return PDO
     */
    public static function getConnection(string $path, array $options = []): PDO
    {
        $key = self::generateConnectionKey($path, $options);

        if (!isset(self::$pdoConnections[$key])) {
            $dsn = "sqlite:" . $path;
            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Apply additional options
            foreach ($options as $attribute => $value) {
                $pdo->setAttribute($attribute, $value);
            }

            self::$pdoConnections[$key] = $pdo;
            self::$transactionStates[$key] = false;
            self::$pragmasApplied[$key] = false; // Mark as needing PRAGMA application
        }

        return self::$pdoConnections[$key];
    }

    /**
     * Check if PRAGMAs have been applied to a connection.
     */
    public static function arePragmasApplied(string $path): bool
    {
        $key = self::generateConnectionKey($path);
        return self::$pragmasApplied[$key] ?? false;
    }

    /**
     * Mark PRAGMAs as applied for a connection.
     */
    public static function markPragmasApplied(string $path): void
    {
        $key = self::generateConnectionKey($path);
        self::$pragmasApplied[$key] = true;
    }

    /**
     * Mark a database connection as being in a transaction.
     */
    public static function markTransactionActive(string $path): void
    {
        $key = self::generateConnectionKey($path);
        self::$transactionStates[$key] = true;
    }

    /**
     * Mark a database connection as not being in a transaction.
     */
    public static function markTransactionInactive(string $path): void
    {
        $key = self::generateConnectionKey($path);
        self::$transactionStates[$key] = false;
    }

    /**
     * Check if a database connection is currently in a transaction.
     */
    public static function isInTransaction(string $path): bool
    {
        $key = self::generateConnectionKey($path);
        return self::$transactionStates[$key] ?? false;
    }

    /**
     * Get the actual transaction state from PDO (for verification).
     */
    public static function getPdoTransactionState(string $path): bool
    {
        $key = self::generateConnectionKey($path);

        if (!isset(self::$pdoConnections[$key])) {
            return false;
        }

        return self::$pdoConnections[$key]->inTransaction();
    }

    /**
     * Close a specific connection.
     */
    public static function closeConnection(string $path): void
    {
        $key = self::generateConnectionKey($path);

        if (isset(self::$pdoConnections[$key])) {
            // Rollback any active transaction before closing
            if (self::$pdoConnections[$key]->inTransaction()) {
                self::$pdoConnections[$key]->rollBack();
            }

            unset(self::$pdoConnections[$key]);
            unset(self::$transactionStates[$key]);
        }
    }

    /**
     * Close all connections in this worker.
     */
    public static function closeAllConnections(): void
    {
        foreach (array_keys(self::$pdoConnections) as $key) {
            if (self::$pdoConnections[$key]->inTransaction()) {
                self::$pdoConnections[$key]->rollBack();
            }
        }

        self::$pdoConnections = [];
        self::$transactionStates = [];
    }

    /**
     * Generate a unique key for a connection based on path and options.
     */
    private static function generateConnectionKey(string $path, array $options = []): string
    {
        if (empty($options)) {
            return $path;
        }

        return $path . '_' . md5(serialize($options));
    }
}
