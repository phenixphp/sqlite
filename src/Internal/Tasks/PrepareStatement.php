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

            $parameterCount = $this->countParameters($this->sql);
            $columnDefinitions = $this->buildColumnDefinitions($stmt);

            return Result::success([
                'parameterCount' => $parameterCount,
                'columnDefinitions' => $columnDefinitions,
            ]);
        } catch (PDOException $e) {
            return Result::failure(message: $e->getMessage());
        }
    }
}
