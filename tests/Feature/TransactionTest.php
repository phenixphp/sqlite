<?php

declare(strict_types=1);

namespace Tests\Feature;

use Amp\Sql\SqlTransactionIsolationLevel;
use Phenix\Sqlite\SqliteConnection;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    private SqliteConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->getConnection();

        $this->connection->query("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL
            )
        ");
    }

    /** @test */
    public function it_begins_and_commits_transaction(): void
    {
        $transaction = $this->connection->beginTransaction();

        $this->assertTrue($transaction->isActive());

        $transaction->query("INSERT INTO users (name, email) VALUES ('Alice', 'alice@example.com')");
        $transaction->query("INSERT INTO users (name, email) VALUES ('Bob', 'bob@example.com')");

        $transaction->commit();

        $this->assertFalse($transaction->isActive());

        $result = $this->connection->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetchRow();

        $this->assertEquals(2, $row['count']);
    }

    /** @test */
    public function it_can_rollback_transaction(): void
    {
        $this->connection->query("INSERT INTO users (name, email) VALUES ('Charlie', 'charlie@example.com')");

        $transaction = $this->connection->beginTransaction();

        $transaction->query("INSERT INTO users (name, email) VALUES ('Dave', 'dave@example.com')");
        $transaction->query("INSERT INTO users (name, email) VALUES ('Eve', 'eve@example.com')");

        $transaction->rollback();

        $this->assertFalse($transaction->isActive());

        $result = $this->connection->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetchRow();

        $this->assertEquals(1, $row['count']);
    }

    /** @test */
    public function it_maintains_isolation_between_queries(): void
    {
        $this->connection->query("INSERT INTO users (name, email) VALUES ('Frank', 'frank@example.com')");

        $transaction = $this->connection->beginTransaction();

        $transaction->query("INSERT INTO users (name, email) VALUES ('Grace', 'grace@example.com')");

        $result = $transaction->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetchRow();

        $this->assertEquals(2, $row['count']);

        $transaction->commit();

        $result = $this->connection->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetchRow();

        $this->assertEquals(2, $row['count']);
    }

    /** @test */
    public function it_releases_busy_lock_after_commit(): void
    {
        $transaction1 = $this->connection->beginTransaction();
        $transaction1->query("INSERT INTO users (name, email) VALUES ('User1', 'user1@example.com')");
        $transaction1->commit();

        $transaction2 = $this->connection->beginTransaction();
        $this->assertTrue($transaction2->isActive());
        $transaction2->rollback();
    }

    /** @test */
    public function it_releases_busy_lock_after_rollback(): void
    {
        $transaction1 = $this->connection->beginTransaction();
        $transaction1->query("INSERT INTO users (name, email) VALUES ('User2', 'user2@example.com')");
        $transaction1->rollback();

        $transaction2 = $this->connection->beginTransaction();
        $this->assertTrue($transaction2->isActive());
        $transaction2->commit();
    }

    /** @test */
    public function it_respects_transaction_isolation_level(): void
    {
        // Test DEFERRED transaction (default)
        $this->connection->setTransactionIsolation(SqlTransactionIsolationLevel::Committed);
        $transaction = $this->connection->beginTransaction();
        $this->assertEquals(SqlTransactionIsolationLevel::Committed, $transaction->getTransactionIsolation());
        $transaction->rollback();

        // Test IMMEDIATE transaction
        $this->connection->setTransactionIsolation(SqlTransactionIsolationLevel::Repeatable);
        $transaction = $this->connection->beginTransaction();
        $this->assertEquals(SqlTransactionIsolationLevel::Repeatable, $transaction->getTransactionIsolation());
        $transaction->rollback();

        // Test EXCLUSIVE transaction
        $this->connection->setTransactionIsolation(SqlTransactionIsolationLevel::Serializable);
        $transaction = $this->connection->beginTransaction();
        $this->assertEquals(SqlTransactionIsolationLevel::Serializable, $transaction->getTransactionIsolation());
        $transaction->rollback();
    }

    /** @test */
    public function it_executes_queries_outside_transaction_after_transaction(): void
    {
        $transaction = $this->connection->beginTransaction();
        $transaction->query("INSERT INTO users (name, email) VALUES ('Test', 'test@example.com')");
        $transaction->commit();

        $result = $this->connection->query("SELECT * FROM users WHERE name = 'Test'");
        $row = $result->fetchRow();

        $this->assertEquals('Test', $row['name']);
        $this->assertEquals('test@example.com', $row['email']);
    }

    /** @test */
    public function it_handles_multiple_operations_in_single_transaction(): void
    {
        $transaction = $this->connection->beginTransaction();

        for ($i = 1; $i <= 5; $i++) {
            $transaction->query("INSERT INTO users (name, email) VALUES ('User$i', 'user$i@example.com')");
        }

        $transaction->query("UPDATE users SET email = 'updated@example.com' WHERE name = 'User1'");

        $transaction->query("DELETE FROM users WHERE name = 'User5'");

        $transaction->commit();

        $result = $this->connection->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetchRow();
        $this->assertEquals(4, $row['count']); // 5 inserts - 1 delete

        $result = $this->connection->query("SELECT email FROM users WHERE name = 'User1'");
        $row = $result->fetchRow();
        $this->assertEquals('updated@example.com', $row['email']);
    }
}
