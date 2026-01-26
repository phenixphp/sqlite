<?php

declare(strict_types=1);

/**
 * Bootstrap file for SQLite workers.
 *
 * This file is executed once when the worker process/thread starts.
 * Variables and functions defined here persist for the entire lifetime of the worker.
 *
 * Key features:
 * - Maintains persistent PDO connections across multiple tasks
 * - Tracks transaction state per database
 * - Provides connection reuse for better performance
 */

// Store persistent PDO connections, keyed by database path
$GLOBALS['sqlite_connections'] = [];

// Track transaction state per database
$GLOBALS['sqlite_transaction_states'] = [];

/**
 * Get or create a persistent PDO connection for the given database path.
 */
$GLOBALS['getConnection'] = function (string $path): PDO {
    static $connections = [];

    if (! isset($connections[$path])) {
        $connections[$path] = new PDO("sqlite:$path");
        $connections[$path]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    return $connections[$path];
};

/**
 * Check if a database is currently in a transaction.
 */
$GLOBALS['isInTransaction'] = function (string $path): bool {
    static $transactionStates = [];

    return $transactionStates[$path] ?? false;
};

/**
 * Set the transaction state for a database.
 */
$GLOBALS['setTransactionState'] = function (string $path, bool $state): void {
    static $transactionStates = [];

    $transactionStates[$path] = $state;
};

/**
 * Get the actual transaction state from PDO (fallback/verification).
 */
$GLOBALS['getPdoTransactionState'] = function (string $path): bool {
    $getConnection = $GLOBALS['getConnection'];
    $pdo = $getConnection($path);

    return $pdo->inTransaction();
};
