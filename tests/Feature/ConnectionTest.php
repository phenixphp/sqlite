<?php

declare(strict_types=1);

namespace Tests\Feature;

use Amp\Sql\SqlTransactionIsolationLevel;
use Phenix\Sqlite\Constants\SqliteJournalMode;
use Phenix\Sqlite\Constants\SqliteSynchronous;
use Phenix\Sqlite\Internal\Exceptions\ConnectionFailureException;
use Phenix\Sqlite\SqliteConfig;
use Phenix\Sqlite\SqliteConnection;
use Tests\TestCase;

use function Phenix\Sqlite\connect;

class ConnectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_connect_to_an_in_memory_database(): void
    {
        $connection = connect();

        $this->assertInstanceOf(SqliteConnection::class, $connection);
        $this->assertFalse($connection->isClosed());

        $connection->close();
    }

    /**
     * @test
     */
    public function it_can_connect_to_a_file_based_database(): void
    {
        $dbPath = sys_get_temp_dir() . '/test_' . uniqid() . '.db';
        $config = SqliteConfig::fromPath($dbPath);

        $connection = SqliteConnection::connect($config);

        $this->assertInstanceOf(SqliteConnection::class, $connection);
        $this->assertFalse($connection->isClosed());
        $this->assertSame($dbPath, $connection->getConfig()->getPath());

        $connection->close();

        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
    }

    /**
     * @test
     */
    public function it_fails_when_connecting_to_invalid_path(): void
    {
        $config = new SqliteConfig(
            path: '/invalid/path/that/does/not/exist/database.db'
        );

        $this->expectException(ConnectionFailureException::class);

        SqliteConnection::connect($config);
    }

    /**
     * @test
     */
    public function it_can_be_closed(): void
    {
        $connection = connect();

        $this->assertFalse($connection->isClosed());

        $connection->close();

        $this->assertTrue($connection->isClosed());
    }

    /**
     * @test
     */
    public function it_can_register_close_callbacks(): void
    {
        $connection = connect();
        $callbackExecuted = false;

        $connection->onClose(function () use (&$callbackExecuted) {
            $callbackExecuted = true;
        });

        $connection->close();

        $this->assertTrue($callbackExecuted);
    }

    /**
     * @test
     */
    public function it_returns_configuration(): void
    {
        $config = new SqliteConfig(
            path: ':memory:',
            journalMode: SqliteJournalMode::Wal,
            synchronous: SqliteSynchronous::Normal,
            foreignKeys: true
        );

        $connection = SqliteConnection::connect($config);
        $returnedConfig = $connection->getConfig();

        $this->assertSame(':memory:', $returnedConfig->getPath());
        $this->assertSame(SqliteJournalMode::Wal, $returnedConfig->getJournalMode());
        $this->assertSame(SqliteSynchronous::Normal, $returnedConfig->getSynchronous());
        $this->assertTrue($returnedConfig->getForeignKeys());
    }

    /**
     * @test
     */
    public function it_tracks_last_used_timestamp(): void
    {
        $connection = connect();

        $timestamp = $connection->getLastUsedAt();

        $this->assertIsInt($timestamp);
        $this->assertLessThanOrEqual(time(), $timestamp);
        $this->assertGreaterThan(time() - 5, $timestamp);
    }

    /**
     * @test
     */
    public function it_can_get_and_set_transaction_isolation(): void
    {
        $connection = connect();

        $this->assertSame(
            SqlTransactionIsolationLevel::Committed,
            $connection->getTransactionIsolation()
        );

        $connection->setTransactionIsolation(SqlTransactionIsolationLevel::Serializable);

        $this->assertSame(
            SqlTransactionIsolationLevel::Serializable,
            $connection->getTransactionIsolation()
        );
    }

    /**
     * @test
     */
    public function it_connects_with_default_concurrency_settings(): void
    {
        $config = SqliteConfig::fromPath(':memory:');
        $connection = SqliteConnection::connect($config);

        $this->assertSame(SqliteJournalMode::Wal, $config->getJournalMode());
        $this->assertSame(SqliteSynchronous::Normal, $config->getSynchronous());
        $this->assertTrue($config->getForeignKeys());
        $this->assertSame(5000, $config->getBusyTimeout());
    }

    /**
     * @test
     */
    public function it_can_connect_with_custom_configuration(): void
    {
        $config = new SqliteConfig(
            path: ':memory:',
            busyTimeout: 10000,
            journalMode: SqliteJournalMode::Delete,
            synchronous: SqliteSynchronous::Full,
            foreignKeys: false,
            cacheSize: -2000
        );

        $connection = SqliteConnection::connect($config);
        $returnedConfig = $connection->getConfig();

        $this->assertSame(10000, $returnedConfig->getBusyTimeout());
        $this->assertSame(SqliteJournalMode::Delete, $returnedConfig->getJournalMode());
        $this->assertSame(SqliteSynchronous::Full, $returnedConfig->getSynchronous());
        $this->assertFalse($returnedConfig->getForeignKeys());
        $this->assertSame(-2000, $returnedConfig->getCacheSize());
    }

    /**
     * @test
     */
    public function it_closes_connection_on_destruct(): void
    {
        $connection = connect();
        $isClosed = false;

        $connection->onClose(function () use (&$isClosed) {
            $isClosed = true;
        });

        unset($connection);

        \Amp\delay(0.1);

        $this->assertTrue($isClosed);
    }
}
