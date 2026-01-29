<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use PDOException;
use Phenix\Sqlite\SqliteConfig;

class PrepareStatement extends ConnectDatabase
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
            $pdo = $this->connect();
            $stmt = $pdo->prepare($this->sql);

            if (! $stmt) {
                return Result::failure(message: "Failed to prepare statement: {$this->sql}");
            }

            $parameterCount = self::countParameters($this->sql);

            $columnDefinitions = [];
            $colCount = $stmt->columnCount();

            for ($i = 0; $i < $colCount; ++$i) {
                $meta = $stmt->getColumnMeta($i);

                $columnDefinitions[] = [
                    'name' => $meta['name'] ?? '',
                    'type' => $meta['native_type'] ?? 'Text',
                    'declaredType' => $meta['sqlite:decl_type'] ?? null,
                    'table' => $meta['table'] ?? null,
                    'length' => $meta['len'] ?? 0,
                    'flags' => 0,
                    'decimals' => 0,
                ];
            }

            return Result::success([
                'parameterCount' => $parameterCount,
                'columnDefinitions' => $columnDefinitions,
            ]);
        } catch (PDOException $e) {
            return Result::failure(message: $e->getMessage());
        }
    }

    private static function countParameters(string $sql): int
    {
        $count = preg_match_all('/\?|:[a-zA-Z_][a-zA-Z0-9_]*/', $sql, $matches);

        return $count ?: 0;
    }
}
