<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal;

use Closure;
use Error;
use Phenix\Sqlite\Contracts\SqliteResult;
use Phenix\Sqlite\Contracts\SqliteStatement;
use Phenix\Sqlite\Internal\Exceptions\SqliteException;
use Phenix\Sqlite\SqliteColumnDefinition;

use function is_int;
use function is_string;

class SqliteConnectionStatement implements SqliteStatement
{
    private array $bindings;

    private int $lastUsedAt;

    private bool $closed;

    /**
     * @param array<SqliteColumnDefinition> $columnDefinitions
     */
    public function __construct(
        private readonly ConnectionProcessor $processor,
        private readonly string $sql,
        private readonly int $parameterCount,
        private readonly array $columnDefinitions,
    ) {
        $this->bindings = [];
        $this->lastUsedAt = time();
        $this->closed = false;
    }

    public function execute(array $params = []): SqliteResult
    {
        if ($this->closed) {
            throw new Error('Statement is closed');
        }

        $bindings = $params ?: $this->bindings;

        $execution = $this->processor->execute(
            sql: $this->sql,
            params: $bindings,
        );

        $result = $execution->await();

        if ($result instanceof Error) {
            throw new SqliteException('Failed to execute statement: ' . $result->getMessage(), 0, $result);
        }

        $this->lastUsedAt = time();

        return $result;
    }

    public function bind(int|string $paramId, string $data): void
    {
        if (is_int($paramId) && ($paramId < 0 || $paramId >= $this->parameterCount)) {
            throw new Error("Invalid parameter index: $paramId");
        }

        if (is_string($paramId) && ! preg_match('/^:[a-zA-Z_]\w*$/', $paramId)) {
            throw new Error("Invalid parameter name: $paramId");
        }

        $this->bindings[$paramId] = $data;
    }

    public function getColumnDefinitions(): array
    {
        return $this->columnDefinitions;
    }

    public function getParameterDefinitions(): array
    {
        return array_fill(0, $this->parameterCount, null);
    }

    public function reset(): void
    {
        $this->bindings = [];
    }

    public function getQuery(): string
    {
        return $this->sql;
    }

    public function getLastUsedAt(): int
    {
        return $this->lastUsedAt;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function close(): void
    {
        $this->closed = true;
        $this->bindings = [];
    }

    public function onClose(Closure $onClose): void
    {
        // No operation needed
    }
}
