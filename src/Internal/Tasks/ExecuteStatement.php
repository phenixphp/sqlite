<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use PDO;
use PDOException;
use Phenix\Sqlite\SqliteConfig;

use function is_int;

class ExecuteStatement extends ConnectDatabase
{
    /**
     * @param SqliteConfig $config
     * @param string $sql
     * @param array<mixed> $params
     */
    public function __construct(
        SqliteConfig $config,
        protected string $sql,
        protected array $params,
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

            foreach ($this->params as $key => $value) {
                $param = is_int($key) ? $key + 1 : $key;

                $stmt->bindValue($param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            $stmt->execute();

            $rows = $stmt->fetchAll();
            $lastInsertId = $pdo->lastInsertId() ?: null;
            $affectedRows = $stmt->rowCount();

            $columnDefinitions = $this->buildColumnDefinitions($stmt);

            return Result::success([
                'rows' => $rows,
                'lastInsertId' => $lastInsertId !== null ? (int) $lastInsertId : null,
                'affectedRows' => $affectedRows,
                'columnDefinitions' => $columnDefinitions,
            ]);
        } catch (PDOException $e) {
            return Result::failure(message: $e->getMessage());
        }
    }
}
