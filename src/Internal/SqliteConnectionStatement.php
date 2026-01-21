<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal;

use Closure;
use Error;
use Phenix\Sqlite\Contracts\SqliteResult;
use Phenix\Sqlite\Contracts\SqliteStatement;
use Phenix\Sqlite\SqliteColumnDefinition;

/**
 * @internal
 */
class SqliteConnectionStatement implements SqliteStatement
{
    /**
     * @param array<SqliteColumnDefinition> $columnDefinitions
     * @param array<SqliteColumnDefinition> $parameterDefinitions
     */
    public function __construct(
        private readonly ConnectionProcessor $processor,
        private readonly string $sql,
        private readonly int|string $statementId,
        private readonly array $columnDefinitions = [],
        private readonly array $parameterDefinitions = [],
    ) {
    }

    public function execute(array $params = []): SqliteResult
    {
        // TODO: Implement statement execution
        // 1. Submit execute task to worker with bound parameters
        // 2. Parse result rows and column definitions
        // 3. Return SqliteConnectionResult
        throw new Error("Statement execution not yet implemented");
    }

    public function bind(int|string $paramId, string $data): void
    {
        // TODO: Implement parameter binding
        // 1. Validate paramId exists in parameterDefinitions
        // 2. Submit bind task to worker
        // 3. Store binding for next execute()
        throw new Error("Parameter binding not yet implemented");
    }

    public function getColumnDefinitions(): array
    {
        return $this->columnDefinitions;
    }

    public function getParameterDefinitions(): array
    {
        return $this->parameterDefinitions;
    }

    public function reset(): void
    {
        // TODO: Implement statement reset
        // 1. Clear all parameter bindings
        // 2. Reset statement state for reuse
        // 3. Submit reset task to worker
        throw new Error("Statement reset not yet implemented");
    }

    public function getQuery(): string
    {
        return $this->sql;
    }

    public function getLastUsedAt(): int
    {
        return $this->processor->getLastUsedAt();
    }

    public function isClosed(): bool
    {
        return $this->processor->isClosed();
    }

    public function close(): void
    {
        // TODO: Implement statement close
        // 1. Close statement handle on worker
        // 2. Free resources
    }

    public function onClose(Closure $onClose): void
    {
        $this->processor->onClose($onClose);
    }
}
