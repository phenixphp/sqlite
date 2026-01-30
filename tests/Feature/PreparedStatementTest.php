<?php

declare(strict_types=1);

use Phenix\Sqlite\SqliteConnection;
use Tests\TestCase;

class PreparedStatementTest extends TestCase
{
    private SqliteConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->getConnection();
    }

    /** @test */
    public function it_prepares_and_executes_insert(): void
    {
        $this->connection->query('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, age INTEGER)');

        $stmt = $this->connection->prepare('INSERT INTO users (name, age) VALUES (?, ?)');
        $result = $stmt->execute(['Alice', 30]);

        $this->assertSame(1, $result->getRowCount());
        $this->assertNotNull($result->getLastInsertId());
    }

    /** @test */
    public function it_prepares_and_executes_select(): void
    {
        $this->connection->query('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, age INTEGER)');
        $this->connection->query("INSERT INTO users (name, age) VALUES ('Bob', 25), ('Carol', 28)");

        $stmt = $this->connection->prepare('SELECT name, age FROM users WHERE age > ?');
        $result = $stmt->execute([26]);

        $rows = iterator_to_array($result->getIterator());

        $this->assertCount(1, $rows);
        $this->assertSame('Carol', $rows[0]['name']);
        $this->assertSame(28, $rows[0]['age']);
    }

    /** @test */
    public function it_prepares_named_parameters(): void
    {
        $this->connection->query('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, age INTEGER)');
        $this->connection->query("INSERT INTO users (name, age) VALUES ('Dan', 40)");

        $stmt = $this->connection->prepare('SELECT name FROM users WHERE age = :age');
        $result = $stmt->execute([':age' => 40]);

        $rows = iterator_to_array($result->getIterator());

        $this->assertCount(1, $rows);
        $this->assertSame('Dan', $rows[0]['name']);
    }

    /** @test */
    public function it_executes_query_directly(): void
    {
        $this->connection->query('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, age INTEGER)');

        $result = $this->connection->execute('INSERT INTO users (name, age) VALUES (?, ?)', ['Alice', 30]);

        $this->assertSame(1, $result->getRowCount());
        $this->assertNotNull($result->getLastInsertId());
    }
}
