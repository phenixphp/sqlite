<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal;

use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Future;
use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\Worker;
use Amp\Sql\SqlTransientResource;
use Closure;
use Error;
use Phenix\Sqlite\Constants\ConnectionState;
use Phenix\Sqlite\Constants\SqliteDataType;
use Phenix\Sqlite\Internal\Exceptions\ConnectionFailureException;
use Phenix\Sqlite\Internal\Tasks\ConnectDatabase;
use Phenix\Sqlite\Internal\Tasks\ExecuteQuery;
use Phenix\Sqlite\Internal\Tasks\Result;
use Phenix\Sqlite\SqliteColumnDefinition;
use Phenix\Sqlite\SqliteConfig;
use SplQueue;

use function Amp\Parallel\Worker\getWorker;
use function time;

class ConnectionProcessor implements SqlTransientResource
{
    use ForbidCloning;
    use ForbidSerialization;

    protected SqliteConfig $config;

    protected readonly SqliteConnectionMetadata $metadata;

    /** @var SplQueue<DeferredFuture> */
    protected readonly SplQueue $deferreds;

    /** @var SplQueue<Closure():void> */
    protected readonly SplQueue $onReady;

    protected int $lastUsedAt;

    protected ConnectionState $connectionState = ConnectionState::Unconnected;

    protected Worker $worker;

    protected array $closeCallbacks = [];

    public function __construct(SqliteConfig $config, Worker|null $worker = null)
    {
        $this->metadata = new SqliteConnectionMetadata();
        $this->config = $config;
        $this->lastUsedAt = time();

        $this->deferreds = new SplQueue();
        $this->onReady = new SplQueue();

        $this->worker = $worker ?? getWorker();
    }

    public function isClosed(): bool
    {
        return match ($this->connectionState) {
            ConnectionState::Closing, ConnectionState::Closed => true,
            default => false,
        };
    }

    public function onClose(Closure $onClose): void
    {
        $this->closeCallbacks[] = $onClose;
    }

    public function isReady(): bool
    {
        return $this->connectionState === ConnectionState::Ready;
    }

    public function getMetadata(): SqliteConnectionMetadata
    {
        return clone $this->metadata;
    }

    public function getConfig(): SqliteConfig
    {
        return $this->config;
    }

    public function getLastUsedAt(): int
    {
        return $this->lastUsedAt;
    }

    public function connect(null|Cancellation $cancellation = null): void
    {
        if ($this->connectionState !== ConnectionState::Unconnected) {
            throw new ConnectionFailureException('Connection already established');
        }

        $this->connectionState = ConnectionState::Connecting;

        $execution = $this->worker->submit(new ConnectDatabase($this->config));

        /** @var Result $result */
        $result = $execution->await($cancellation);

        if ($result->failed()) {
            throw new ConnectionFailureException($result->message() ?? "Failed to open database connection");
        }

        $this->connectionState = ConnectionState::Ready;
        $this->lastUsedAt = time();
    }

    /**
     * @return Future<SqliteConnectionResult>
     */
    public function query(string $query): Future
    {
        return $this->executeQuery(new ExecuteQuery($this->config, $query));
    }

    /**
     * @return Future<bool>
     */
    public function beginTransaction(): Future
    {
        if ($this->isClosed()) {
            $this->throwConnectionException();
        }

        $execution = $this->worker->submit(new Tasks\BeginTransaction($this->config));

        /** @var Result $taskResult */
        $taskResult = $execution->await();

        if ($taskResult->failed()) {
            $deferred = new DeferredFuture();
            $deferred->error(new Error($taskResult->message() ?? "Failed to begin transaction"));

            return $deferred->getFuture();
        }

        $this->lastUsedAt = time();

        $deferred = new DeferredFuture();
        $deferred->complete(true);

        return $deferred->getFuture();
    }

    /**
     * @return Future<bool>
     */
    public function commitTransaction(): Future
    {
        if ($this->isClosed()) {
            $this->throwConnectionException();
        }

        $execution = $this->worker->submit(new Tasks\CommitTransaction($this->config));

        /** @var Result $taskResult */
        $taskResult = $execution->await();

        if ($taskResult->failed()) {
            $deferred = new DeferredFuture();
            $deferred->error(new Error($taskResult->message() ?? "Failed to commit transaction"));

            return $deferred->getFuture();
        }

        $this->lastUsedAt = time();

        $deferred = new DeferredFuture();
        $deferred->complete(true);

        return $deferred->getFuture();
    }

    /**
     * @return Future<bool>
     */
    public function rollbackTransaction(): Future
    {
        if ($this->isClosed()) {
            $this->throwConnectionException();
        }

        $execution = $this->worker->submit(new Tasks\RollbackTransaction($this->config));


        /** @var Result $taskResult */
        $taskResult = $execution->await();

        if ($taskResult->failed()) {
            $deferred = new DeferredFuture();
            $deferred->error(new Error($taskResult->message() ?? "Failed to rollback transaction"));

            return $deferred->getFuture();
        }

        $this->lastUsedAt = time();

        $deferred = new DeferredFuture();
        $deferred->complete(true);

        return $deferred->getFuture();
    }

    public function execute(string $sql, array $params = []): Future
    {
        return $this->executeQuery(new Tasks\ExecuteStatement($this->config, $sql, $params));
    }

    public function close(): void
    {
        if ($this->isClosed()) {
            return;
        }

        $this->connectionState = ConnectionState::Closing;

        $this->connectionState = ConnectionState::Closed;

        foreach ($this->closeCallbacks as $callback) {
            $callback();
        }
    }

    public function countParameters(string $sql): int
    {
        $count = preg_match_all('/\?|:[a-zA-Z_][a-zA-Z0-9_]*/', $sql, $matches);

        return $count ?: 0;
    }

    /**
     * @param array<array{name: string, type: string, declaredType: string|null, table: string|null, length: int, flags: int, decimals: int}>|null $columnData
     * @return array<SqliteColumnDefinition>|null
     */
    protected function buildColumnDefinitions(array|null $columnData): array|null
    {
        if ($columnData === null) {
            return null;
        }

        $definitions = [];

        foreach ($columnData as $column) {
            $typeEnum = match ($column['type']) {
                'Null' => SqliteDataType::Null,
                'Integer' => SqliteDataType::Integer,
                'Real' => SqliteDataType::Real,
                'Text' => SqliteDataType::Text,
                'Blob' => SqliteDataType::Blob,
                default => SqliteDataType::Text,
            };

            $definitions[] = new SqliteColumnDefinition(
                table: $column['table'] ?? '',
                name: $column['name'],
                length: $column['length'],
                type: $typeEnum,
                flags: $column['flags'],
                decimals: $column['decimals'],
                defaults: '',
                originalTable: $column['table'],
                originalName: $column['name'],
                declaredType: $column['declaredType'],
                schema: null,
            );
        }

        return $definitions;
    }

    protected function executeQuery(Task $task): Future
    {
        if ($this->isClosed()) {
            $this->throwConnectionException();
        }

        $execution = $this->worker->submit($task);

        /** @var Result $taskResult */
        $taskResult = $execution->await();

        if ($taskResult->failed()) {
            $deferred = new DeferredFuture();
            $deferred->error(new Error($taskResult->message() ?? 'Query execution failed'));

            return $deferred->getFuture();
        }

        $data = $taskResult->output();

        $columnDefinitions = $this->buildColumnDefinitions($data['columnDefinitions']);

        $result = new SqliteConnectionResult(
            columnDefinitions: $columnDefinitions,
            rows: $data['rows'],
            lastInsertId: $data['lastInsertId'],
            affectedRows: $data['affectedRows'],
        );

        $this->lastUsedAt = time();

        $deferred = new DeferredFuture();
        $deferred->complete($result);

        return $deferred->getFuture();
    }

    protected function throwConnectionException(): never
    {
        throw new ConnectionFailureException('The connection has been closed');
    }
}
