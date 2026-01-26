<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal;

use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Future;
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

use function time;

class ConnectionProcessor implements SqlTransientResource
{
    use ForbidCloning;
    use ForbidSerialization;

    private SqliteConfig $config;

    private readonly SqliteConnectionMetadata $metadata;

    /** @var SplQueue<DeferredFuture> */
    private readonly SplQueue $deferreds;

    /** @var SplQueue<Closure():void> */
    private readonly SplQueue $onReady;

    private int $lastUsedAt;

    private ConnectionState $connectionState = ConnectionState::Unconnected;

    private Worker $worker;

    private array $closeCallbacks = [];

    public function __construct(SqliteConfig $config)
    {
        $this->metadata = new SqliteConnectionMetadata();
        $this->config = $config;
        $this->lastUsedAt = time();

        $this->deferreds = new SplQueue();
        $this->onReady = new SplQueue();

        $this->worker = SqliteWorkerFactory::create();
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
            throw new Error('Connection already established');
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
        if ($this->isClosed()) {
            throw new Error('The connection has been closed');
        }

        $execution = $this->worker->submit(new ExecuteQuery($this->config, $query));

        /** @var Result $taskResult */
        $taskResult = $execution->await();

        if ($taskResult->failed()) {
            $deferred = new DeferredFuture();
            $deferred->error(new Error($taskResult->message() ?? "Query execution failed"));

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

    /**
     * @return Future<bool>
     */
    public function beginTransaction(string $transactionType): Future
    {
        if ($this->isClosed()) {
            throw new Error('The connection has been closed');
        }

        $execution = $this->worker->submit(new Tasks\BeginTransaction($this->config, $transactionType));

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
            throw new Error('The connection has been closed');
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
            throw new Error('The connection has been closed');
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

    /**
     * @return Future<SqliteConnectionStatement>
     */
    public function prepare(string $sql): Future
    {
        if ($this->isClosed()) {
            throw new Error("The connection has been closed");
        }

        // TODO: Implement prepared statement
        // Strategy:
        // 1. Parse parameter placeholders locally (? and :name)
        // 2. Create PrepareStatement task with SQL and config
        // 3. Task opens PDO in worker, calls prepare() to get metadata
        // 4. Task returns serializable data:
        //    - parameterCount: int
        //    - columnDefinitions: array with metadata
        // 5. Store SQL and metadata locally
        // 6. Return SqliteConnectionStatement that will execute via ExecuteStatement task
        // 7. Each execute() sends new task with SQL + bound params
        // Note: Can't maintain persistent prepared statement across tasks

        $this->lastUsedAt = time();

        $deferred = new DeferredFuture();
        $deferred->error(new Error("Prepare not yet implemented"));

        return $deferred->getFuture();
    }

    public function close(): void
    {
        if ($this->isClosed()) {
            return;
        }

        $this->connectionState = ConnectionState::Closing;

        // TODO: Implement connection cleanup
        // Strategy:
        // 1. Mark all pending deferreds as failed
        // 2. No need to close PDO - each task manages its own connection
        // 3. Worker pool automatically manages worker lifecycle
        // 4. For transactions: send rollback task if active
        // Note: Workers are reused by the pool, don't shutdown manually

        $this->connectionState = ConnectionState::Closed;

        foreach ($this->closeCallbacks as $callback) {
            $callback();
        }
    }

    /**
     * @param array<array{name: string, type: string, declaredType: string|null, table: string|null, length: int, flags: int, decimals: int}>|null $columnData
     * @return array<SqliteColumnDefinition>|null
     */
    private function buildColumnDefinitions(array|null $columnData): array|null
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
}
