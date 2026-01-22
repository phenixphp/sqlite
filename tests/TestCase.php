<?php

declare(strict_types=1);

namespace Tests;

use Amp\PHPUnit\AsyncTestCase;
use Phenix\Sqlite\SqliteConfig;
use Phenix\Sqlite\SqliteConnection;

class TestCase extends AsyncTestCase
{
    protected string|null $databasePath = null;

    protected function getDatabasePath(): string
    {
        if ($this->databasePath === null) {
            $this->databasePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sqlite_test_' . bin2hex(random_bytes(8)) . '.db';
        }

        return $this->databasePath;
    }

    protected function getConnection(): SqliteConnection
    {
        $config = SqliteConfig::fromPath($this->getDatabasePath());

        return SqliteConnection::connect($config);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->databasePath !== null && file_exists($this->databasePath)) {
            unlink($this->databasePath);
        }
    }
}
