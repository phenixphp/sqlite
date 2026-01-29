<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal;

use Amp\Sql\SqlTransactionIsolation;
use Closure;
use Error;
use Phenix\Sqlite\Contracts\SqliteResult;
use Phenix\Sqlite\Contracts\SqliteStatement;
use Phenix\Sqlite\Contracts\SqliteTransaction;
use Phenix\Sqlite\Internal\Exceptions\QueryExecutionException;

class SqliteConnectionTransaction implements SqliteTransaction
{
    private bool $active = true;

    /** @var list<Closure():void> */
    private array $onCommitCallbacks = [];

    /** @var list<Closure():void> */
    private array $onRollbackCallbacks = [];

    public function __construct(
        private readonly TransactionConnectionProcessor $processor,
        private readonly SqlTransactionIsolation $isolation,
        private readonly Closure $release,
        private readonly string|null $savepointId = null,
    ) {
    }

    public function query(string $sql): SqliteResult
    {
        if (! $this->active) {
            throw new Error('Transaction is not active');
        }

        return $this->processor->query($sql)->await();
    }

    public function prepare(string $sql): SqliteStatement
    {
        if (! $this->active) {
            throw new Error('Transaction is not active');
        }

        $data = $this->processor->prepare($sql)->await();

        if ($data instanceof Error) {
            throw new QueryExecutionException('Failed to prepare statement: ' . $data->getMessage());
        }

        return new SqliteConnectionStatement(
            processor: $this->processor,
            sql: $sql,
            parameterCount: $data['parameterCount'] ?? 0,
            columnDefinitions: $data['columnDefinitions'] ?? [],
        );
    }

    public function execute(string $sql, array $params = []): SqliteResult
    {
        $statement = $this->prepare($sql);

        return $statement->execute($params);
    }

    public function beginTransaction(): SqliteTransaction
    {
        if (! $this->active) {
            throw new Error("Transaction is not active");
        }

        // TODO: Implement nested transaction using SAVEPOINT
        // SQLite doesn't support nested BEGIN/COMMIT, use SAVEPOINT instead:
        // 1. Generate unique savepoint name
        // 2. Execute "SAVEPOINT sp_name"
        // 3. Return new SqliteConnectionTransaction with savepoint release logic
        throw new Error("Nested transactions (SAVEPOINTs) not yet implemented");
    }

    public function commit(): void
    {
        if (! $this->active) {
            throw new Error("Transaction is not active");
        }

        // Handle nested transaction (savepoint)
        if ($this->savepointId !== null) {
            $this->processor->query("RELEASE SAVEPOINT {$this->savepointId}")->await();
        } else {
            // Commit main transaction
            $this->processor->commitTransaction()->await();
        }

        $this->active = false;
        ($this->release)();

        foreach ($this->onCommitCallbacks as $callback) {
            $callback();
        }

        $this->processor->shutdown();
    }

    public function rollback(): void
    {
        if (! $this->active) {
            throw new Error("Transaction is not active");
        }

        // Handle nested transaction (savepoint)
        if ($this->savepointId !== null) {
            $this->processor->query("ROLLBACK TO SAVEPOINT {$this->savepointId}")->await();
            $this->processor->query("RELEASE SAVEPOINT {$this->savepointId}")->await();
        } else {
            // Rollback main transaction
            $this->processor->rollbackTransaction()->await();
        }

        $this->active = false;
        ($this->release)();

        foreach ($this->onRollbackCallbacks as $callback) {
            $callback();
        }

        $this->processor->shutdown();
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getTransactionIsolation(): SqlTransactionIsolation
    {
        return $this->isolation;
    }

    public function getIsolation(): SqlTransactionIsolation
    {
        return $this->isolation;
    }

    public function getSavepointIdentifier(): string|null
    {
        return $this->savepointId;
    }

    public function onCommit(Closure $onCommit): void
    {
        $this->onCommitCallbacks[] = $onCommit;
    }

    public function onRollback(Closure $onRollback): void
    {
        $this->onRollbackCallbacks[] = $onRollback;
    }

    public function isClosed(): bool
    {
        return $this->processor->isClosed();
    }

    public function getLastUsedAt(): int
    {
        return $this->processor->getLastUsedAt();
    }

    public function close(): void
    {
        if ($this->active) {
            $this->rollback();
        }
    }

    public function onClose(Closure $onClose): void
    {
        $this->processor->onClose($onClose);
    }
}
