<?php

declare(strict_types=1);

namespace Phenix\Sqlite;

use Amp\Cancellation;
use Amp\DeferredFuture;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Sql\SqlTransactionIsolation;
use Amp\Sql\SqlTransactionIsolationLevel;
use Closure;
use Phenix\Sqlite\Contracts\SqliteConnection as SqliteConnectionContract;
use Phenix\Sqlite\Contracts\SqliteResult;
use Phenix\Sqlite\Contracts\SqliteStatement;
use Phenix\Sqlite\Contracts\SqliteTransaction;
use Phenix\Sqlite\Internal\SqliteWorkerFactory;
use Revolt\EventLoop;
use RuntimeException;
use Throwable;

class SqliteConnection implements SqliteConnectionContract
{
    use ForbidCloning;
    use ForbidSerialization;

    private SqlTransactionIsolation $transactionIsolation = SqlTransactionIsolationLevel::Committed;

    private null|DeferredFuture $busy = null;

    private readonly Closure $release;

    public static function connect(
        SqliteConfig $config,
        null|Cancellation $cancellation = null,
    ): self {
        $processor = new Internal\ConnectionProcessor($config);
        $processor->connect($cancellation);

        return new self($processor, $config);
    }

    private function __construct(
        private readonly Internal\ConnectionProcessor $processor,
        protected readonly SqliteConfig $config
    ) {
        $busy = &$this->busy;
        $this->release = static function () use (&$busy): void {
            $busy?->complete();
            $busy = null;
        };
    }

    public function getConfig(): SqliteConfig
    {
        return $this->config;
    }

    public function getTransactionIsolation(): SqlTransactionIsolation
    {
        return $this->transactionIsolation;
    }

    public function setTransactionIsolation(SqlTransactionIsolation $isolation): void
    {
        $this->transactionIsolation = $isolation;
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
        if (! $this->processor->isClosed()) {
            $this->processor->close();
        }
    }

    public function onClose(Closure $onClose): void
    {
        $this->processor->onClose($onClose);
    }

    public function query(string $sql): SqliteResult
    {
        while ($this->busy) {
            $this->busy->getFuture()->await();
        }

        return $this->processor->query($sql)->await();
    }

    public function beginTransaction(): SqliteTransaction
    {
        while ($this->busy) {
            $this->busy->getFuture()->await();
        }

        $this->busy = $deferred = new DeferredFuture();

        $processor = new Internal\ConnectionProcessor($this->config, SqliteWorkerFactory::create());

        try {
            $result = $processor->beginTransaction()->await();

            if (! $result) {
                throw new RuntimeException('Failed to begin transaction');
            }
        } catch (Throwable $exception) {
            $this->busy = null;
            $deferred->complete();

            throw $exception;
        }

        return new Internal\SqliteConnectionTransaction(
            $processor,
            $this->transactionIsolation,
            $this->release,
        );
    }

    public function prepare(string $sql): SqliteStatement
    {
        while ($this->busy) {
            $this->busy->getFuture()->await();
        }

        // TODO: Implement SQLite prepared statement
        return $this->processor->prepare($sql)->await();
    }

    public function execute(string $sql, array $params = []): SqliteResult
    {
        $statement = $this->prepare($sql);

        return $statement->execute($params);
    }

    public function __destruct()
    {
        $processor = $this->processor;
        EventLoop::queue(static fn () => $processor->close());
    }
}
