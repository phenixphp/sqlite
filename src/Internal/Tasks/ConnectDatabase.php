<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use PDO;
use PDOStatement;
use Phenix\Sqlite\Constants\SqliteDataType;
use Phenix\Sqlite\SqliteConfig;
use Throwable;

use function is_array;
use function sprintf;

class ConnectDatabase implements Task
{
    public function __construct(
        protected SqliteConfig $config
    ) {
    }

    public function run(Channel $channel, Cancellation $cancellation): Result
    {
        try {
            $this->connect();

            return Result::success();
        } catch (Throwable $e) {
            return Result::failure(null, $e->getMessage());
        }
    }

    protected function connect(): PDO
    {
        $dsn = sprintf(
            'sqlite:%s',
            $this->config->getPath()
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        $pdo = new PDO($dsn, null, null, $options);

        $this->applyPragmas($pdo);

        return $pdo;
    }

    protected function applyPragmas(PDO $pdo): void
    {
        $pdo->exec(sprintf('PRAGMA busy_timeout = %d', $this->config->getBusyTimeout()));

        if ($this->config->getJournalMode() !== null) {
            $pdo->exec(sprintf(
                'PRAGMA journal_mode = %s',
                $this->config->getJournalMode()->toPragmaValue()
            ));
        }

        if ($this->config->getSynchronous() !== null) {
            $pdo->exec(sprintf(
                'PRAGMA synchronous = %s',
                $this->config->getSynchronous()->toPragmaValue()
            ));
        }

        if ($this->config->getForeignKeys() !== null) {
            $pdo->exec(sprintf(
                'PRAGMA foreign_keys = %s',
                $this->config->getForeignKeys() ? 'ON' : 'OFF'
            ));
        }

        if ($this->config->getCacheSize() !== null) {
            $pdo->exec(sprintf('PRAGMA cache_size = %d', $this->config->getCacheSize()));
        }
    }

    /**
     * @return array<array{name: string, type: string, declaredType: string|null, table: string|null, length: int, flags: int, decimals: int}>
     */
    protected function extractColumnDefinitions(PDOStatement $stmt): array
    {
        $columnCount = $stmt->columnCount();
        $definitions = [];

        for ($i = 0; $i < $columnCount; $i++) {
            $meta = $stmt->getColumnMeta($i);

            if ($meta === false) {
                continue;
            }

            $typeSource = $meta['sqlite:decl_type'] ?? $meta['native_type'] ?? 'TEXT';
            $sqliteType = $this->mapPdoTypeToSqliteType($typeSource);

            $definitions[] = [
                'name' => $meta['name'] ?? "column_$i",
                'type' => $sqliteType,
                'declaredType' => $meta['sqlite:decl_type'] ?? null,
                'table' => $meta['table'] ?? null,
                'length' => $meta['len'] ?? 0,
                'flags' => is_array($meta['flags'] ?? 0) ? 0 : ($meta['flags'] ?? 0),
                'decimals' => 0,
            ];
        }

        return $definitions;
    }

    protected function mapPdoTypeToSqliteType(string $nativeType): string
    {
        return match (strtoupper($nativeType)) {
            'INTEGER' => SqliteDataType::Integer->name,
            'FLOAT', 'REAL', 'DOUBLE' => SqliteDataType::Real->name,
            'BLOB' => SqliteDataType::Blob->name,
            'NULL' => SqliteDataType::Null->name,
            default => SqliteDataType::Text->name,
        };
    }

    protected function countParameters(string $sql): int
    {
        $count = preg_match_all('/\?|:[a-zA-Z_][a-zA-Z0-9_]*/', $sql, $matches);

        return $count ?: 0;
    }
}
