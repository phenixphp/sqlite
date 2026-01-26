<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use PDO;
use PDOStatement;
use Phenix\Sqlite\Constants\SqliteDataType;
use Phenix\Sqlite\SqliteConfig;
use Throwable;

class ExecuteQuery extends ConnectDatabase
{
    public function __construct(
        SqliteConfig $config,
        private readonly string $sql
    ) {
        parent::__construct($config);
    }

    public function run(Channel $channel, Cancellation $cancellation): Result
    {
        try {
            // Store database path for transaction tasks
            $GLOBALS['current_db_path'] = $this->config->getPath();

            $pdo = $this->connect();

            $stmt = $pdo->query($this->sql);

            $isSelect = $stmt->columnCount() > 0;

            if ($isSelect) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $columnDefinitions = $this->extractColumnDefinitions($stmt);

                return Result::success([
                    'rows' => $rows,
                    'columnDefinitions' => $columnDefinitions,
                    'lastInsertId' => null,
                    'affectedRows' => count($rows),
                ]);
            }

            $affectedRows = $stmt->rowCount();
            $lastInsertId = $pdo->lastInsertId();

            return Result::success([
                'rows' => [],
                'columnDefinitions' => null,
                'lastInsertId' => $lastInsertId !== '0' ? (int) $lastInsertId : null,
                'affectedRows' => $affectedRows,
            ]);
        } catch (Throwable $e) {
            return Result::failure(null, $e->getMessage());
        }
    }

    /**
     * @return array<array{name: string, type: string, declaredType: string|null, table: string|null}>
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
}
