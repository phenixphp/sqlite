<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal\Tasks;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use PDO;
use Phenix\Sqlite\SqliteConfig;
use Throwable;

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

        return $pdo;
    }
}
