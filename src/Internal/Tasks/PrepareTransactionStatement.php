<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Sqlite\SqliteConfig;
use Throwable;

class PrepareTransactionStatement extends TransactionTask
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

            if (! $pdo->inTransaction()) {
                return Result::failure(null, 'Not in a transaction - cannot execute  prepared statement|');
            }

            $stmt = $pdo->prepare($this->sql);

            if (! $stmt) {
                return Result::failure(message: "Failed to prepare statement: {$this->sql}");
            }

            $parameterCount = $this->countParameters($this->sql);

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
        } catch (Throwable $e) {
            return Result::failure(null, $e->getMessage());
        }
    }
}
