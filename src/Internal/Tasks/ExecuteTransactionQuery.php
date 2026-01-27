<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use PDO;
use Phenix\Sqlite\SqliteConfig;
use Throwable;

class ExecuteTransactionQuery extends TransactionTask
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
                return Result::failure(null, 'Not in a transaction - cannot execute transaction query');
            }

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
}
