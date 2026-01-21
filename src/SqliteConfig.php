<?php

declare(strict_types=1);

namespace Phenix\Sqlite;

use Amp\Sql\SqlConfig;
use Error;
use Phenix\Sqlite\Constants\SqliteJournalMode;
use Phenix\Sqlite\Constants\SqliteSynchronous;

class SqliteConfig extends SqlConfig
{
    public const OPEN_READONLY = 0x00000001;
    public const OPEN_READWRITE = 0x00000002;
    public const OPEN_CREATE = 0x00000004;

    public const DEFAULT_OPEN_FLAGS = self::OPEN_READWRITE | self::OPEN_CREATE;
    public const DEFAULT_BUSY_TIMEOUT = 5000; // milliseconds
    public const DEFAULT_JOURNAL_MODE = SqliteJournalMode::Wal;
    public const DEFAULT_SYNCHRONOUS = SqliteSynchronous::Normal;

    public const KEY_MAP = [
        ...parent::KEY_MAP,
        'path' => 'db',
        'file' => 'db',
        'timeout' => 'busy-timeout',
        'journal' => 'journal-mode',
        'sync' => 'synchronous',
        'fk' => 'foreign-keys',
        'cache' => 'cache-size',
    ];

    public static function fromString(string $connectionString): self
    {
        $parts = self::parseConnectionString($connectionString, self::KEY_MAP);

        if (! isset($parts['db'])) {
            throw new Error('Database path must be provided in connection string');
        }

        return new self(
            path: $parts['db'],
            openFlags: (int) ($parts['flags'] ?? self::DEFAULT_OPEN_FLAGS),
            busyTimeout: (int) ($parts['busy-timeout'] ?? self::DEFAULT_BUSY_TIMEOUT),
            journalMode: isset($parts['journal-mode']) ? SqliteJournalMode::from(strtoupper($parts['journal-mode'])) : null,
            synchronous: isset($parts['synchronous']) ? SqliteSynchronous::from(strtoupper($parts['synchronous'])) : null,
            foreignKeys: isset($parts['foreign-keys']) ? $parts['foreign-keys'] === 'on' : null,
            cacheSize: isset($parts['cache-size']) ? (int) $parts['cache-size'] : null,
        );
    }

    public static function fromPath(string $path): self
    {
        return new self(path: $path);
    }

    public function __construct(
        private readonly string $path,
        private readonly int $openFlags = self::DEFAULT_OPEN_FLAGS,
        private readonly int $busyTimeout = self::DEFAULT_BUSY_TIMEOUT,
        private readonly SqliteJournalMode|null $journalMode = self::DEFAULT_JOURNAL_MODE,
        private readonly SqliteSynchronous|null $synchronous = self::DEFAULT_SYNCHRONOUS,
        private readonly bool|null $foreignKeys = true,
        private readonly int|null $cacheSize = null,
    ) {
        parent::__construct(
            host: '',
            port: 0,
            user: null,
            password: null,
            database: $path,
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getConnectionString(): string
    {
        return 'sqlite:' . $this->path;
    }

    public function getOpenFlags(): int
    {
        return $this->openFlags;
    }

    public function withOpenFlags(int $flags): self
    {
        return new self(
            path: $this->path,
            openFlags: $flags,
            busyTimeout: $this->busyTimeout,
            journalMode: $this->journalMode,
            synchronous: $this->synchronous,
            foreignKeys: $this->foreignKeys,
            cacheSize: $this->cacheSize,
        );
    }

    public function getBusyTimeout(): int
    {
        return $this->busyTimeout;
    }

    public function withBusyTimeout(int $timeout): self
    {
        return new self(
            path: $this->path,
            openFlags: $this->openFlags,
            busyTimeout: $timeout,
            journalMode: $this->journalMode,
            synchronous: $this->synchronous,
            foreignKeys: $this->foreignKeys,
            cacheSize: $this->cacheSize,
        );
    }

    public function getJournalMode(): SqliteJournalMode|null
    {
        return $this->journalMode;
    }

    public function withJournalMode(SqliteJournalMode|null $mode): self
    {
        return new self(
            path: $this->path,
            openFlags: $this->openFlags,
            busyTimeout: $this->busyTimeout,
            journalMode: $mode,
            synchronous: $this->synchronous,
            foreignKeys: $this->foreignKeys,
            cacheSize: $this->cacheSize,
        );
    }

    public function getSynchronous(): SqliteSynchronous|null
    {
        return $this->synchronous;
    }

    public function withSynchronous(SqliteSynchronous|null $mode): self
    {
        return new self(
            path: $this->path,
            openFlags: $this->openFlags,
            busyTimeout: $this->busyTimeout,
            journalMode: $this->journalMode,
            synchronous: $mode,
            foreignKeys: $this->foreignKeys,
            cacheSize: $this->cacheSize,
        );
    }

    public function getForeignKeys(): bool|null
    {
        return $this->foreignKeys;
    }

    public function withForeignKeys(bool|null $enabled): self
    {
        return new self(
            path: $this->path,
            openFlags: $this->openFlags,
            busyTimeout: $this->busyTimeout,
            journalMode: $this->journalMode,
            synchronous: $this->synchronous,
            foreignKeys: $enabled,
            cacheSize: $this->cacheSize,
        );
    }

    public function getCacheSize(): int|null
    {
        return $this->cacheSize;
    }

    public function withCacheSize(int|null $size): self
    {
        return new self(
            path: $this->path,
            openFlags: $this->openFlags,
            busyTimeout: $this->busyTimeout,
            journalMode: $this->journalMode,
            synchronous: $this->synchronous,
            foreignKeys: $this->foreignKeys,
            cacheSize: $size,
        );
    }
}
