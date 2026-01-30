<?php

declare(strict_types=1);

namespace Tests\Feature;

use Amp\DeferredFuture;
use Phenix\Sqlite\Constants\SqliteDataType;
use Phenix\Sqlite\SqliteColumnDefinition;
use ReflectionClass;
use Tests\TestCase;

use function Amp\async;
use function Amp\delay;

class QueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_a_table(): void
    {
        $connection = $this->getConnection();

        $sql = <<<'SQL'
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                age INTEGER,
                balance REAL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        SQL;

        $result = $connection->query($sql);

        $this->assertNull($result->getColumnDefinitions());
        $this->assertSame(0, $result->getRowCount());
        $this->assertNull($result->getColumnCount());

        $connection->close();
    }

    /**
     * @test
     */
    public function it_can_insert_data(): void
    {
        $connection = $this->getConnection();

        $connection->query('CREATE TABLE test_insert (id INTEGER PRIMARY KEY, name TEXT)');

        $result = $connection->query("INSERT INTO test_insert (name) VALUES ('John Doe')");

        $this->assertSame(1, $result->getRowCount());
        $this->assertSame(1, $result->getLastInsertId());
        $this->assertNull($result->getColumnDefinitions());

        $connection->close();
    }

    /**
     * @test
     */
    public function it_can_insert_multiple_rows(): void
    {
        $connection = $this->getConnection();

        $connection->query('CREATE TABLE test_multi (id INTEGER PRIMARY KEY, name TEXT)');

        $result = $connection->query(
            "INSERT INTO test_multi (name) VALUES ('John'), ('Jane'), ('Bob')"
        );

        $this->assertSame(3, $result->getRowCount());
        $this->assertSame(3, $result->getLastInsertId());

        $connection->close();
    }

    /**
     * @test
     */
    public function it_can_select_data(): void
    {
        $connection = $this->getConnection();

        $connection->query('CREATE TABLE test_select (id INTEGER PRIMARY KEY, name TEXT, age INTEGER)');
        $connection->query("INSERT INTO test_select (name, age) VALUES ('John', 25), ('Jane', 30)");

        $result = $connection->query('SELECT * FROM test_select');

        $this->assertSame(3, $result->getColumnCount());
        $rows = iterator_to_array($result);

        $this->assertCount(2, $rows);
        $this->assertSame('John', $rows[0]['name']);
        $this->assertSame(25, $rows[0]['age']);
        $this->assertSame('Jane', $rows[1]['name']);
        $this->assertSame(30, $rows[1]['age']);

        $connection->close();
    }

    /**
     * @test
     */
    public function it_returns_column_definitions_for_select_queries(): void
    {
        $connection = $this->getConnection();

        $connection->query('CREATE TABLE products (id INTEGER, name TEXT, price REAL)');
        $connection->query("INSERT INTO products VALUES (1, 'Product', 9.99)");

        $result = $connection->query('SELECT * FROM products');

        $definitions = $result->getColumnDefinitions();

        $this->assertIsArray($definitions);
        $this->assertCount(3, $definitions);

        $this->assertInstanceOf(SqliteColumnDefinition::class, $definitions[0]);
        $this->assertSame('id', $definitions[0]->getName());
        $this->assertEquals(SqliteDataType::Integer, $definitions[0]->getType());

        $this->assertInstanceOf(SqliteColumnDefinition::class, $definitions[1]);
        $this->assertSame('name', $definitions[1]->getName());
        $this->assertEquals(SqliteDataType::Text, $definitions[1]->getType());

        $this->assertInstanceOf(SqliteColumnDefinition::class, $definitions[2]);
        $this->assertSame('price', $definitions[2]->getName());
        $this->assertEquals(SqliteDataType::Real, $definitions[2]->getType());

        $connection->close();
    }

    /**
     * @test
     */
    public function it_can_update_data(): void
    {
        $connection = $this->getConnection();

        $connection->query('CREATE TABLE test_update (id INTEGER PRIMARY KEY, name TEXT, age INTEGER)');
        $connection->query("INSERT INTO test_update (name, age) VALUES ('John', 25)");

        $result = $connection->query("UPDATE test_update SET age = 26 WHERE name = 'John'");

        $this->assertSame(1, $result->getRowCount());
        $this->assertNull($result->getColumnDefinitions());

        $selectResult = $connection->query('SELECT age FROM test_update WHERE name = "John"');
        $row = $selectResult->fetchRow();

        $this->assertSame(26, $row['age']);

        $connection->close();
    }

    /**
     * @test
     */
    public function it_can_delete_data(): void
    {
        $connection = $this->getConnection();

        $connection->query('CREATE TABLE test_delete (id INTEGER PRIMARY KEY, name TEXT)');
        $connection->query("INSERT INTO test_delete (name) VALUES ('John'), ('Jane')");

        $result = $connection->query("DELETE FROM test_delete WHERE name = 'John'");

        $this->assertSame(1, $result->getRowCount());

        $selectResult = $connection->query('SELECT COUNT(*) as count FROM test_delete');
        $row = $selectResult->fetchRow();

        $this->assertSame(1, $row['count']);

        $connection->close();
    }

    /**
     * @test
     */
    public function it_handles_empty_result_sets(): void
    {
        $connection = $this->getConnection();

        $connection->query('CREATE TABLE test_empty (id INTEGER, name TEXT)');

        $result = $connection->query('SELECT * FROM test_empty');

        $this->assertSame(0, $result->getRowCount());
        $this->assertCount(0, iterator_to_array($result));
        $this->assertNotNull($result->getColumnDefinitions());
        $this->assertSame(2, $result->getColumnCount());

        $connection->close();
    }

    /**
     * @test
     */
    public function it_can_execute_complex_queries_with_joins(): void
    {
        $connection = $this->getConnection();

        $connection->query('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');
        $connection->query('CREATE TABLE test_orders (id INTEGER PRIMARY KEY, user_id INTEGER, product TEXT)');

        $connection->query("INSERT INTO test_users (id, name) VALUES (1, 'John')");
        $connection->query("INSERT INTO test_orders (user_id, product) VALUES (1, 'Laptop')");

        $sql = <<<'SQL'
            SELECT test_users.name, test_orders.product
            FROM test_users
            INNER JOIN test_orders ON test_users.id = test_orders.user_id
        SQL;

        $result = $connection->query($sql);

        $rows = iterator_to_array($result);

        $this->assertCount(1, $rows);
        $this->assertSame('John', $rows[0]['name']);
        $this->assertSame('Laptop', $rows[0]['product']);

        $connection->close();
    }

    /**
     * @test
     */
    public function it_can_handle_different_data_types(): void
    {
        $connection = $this->getConnection();

        $connection->query(
            'CREATE TABLE test_types (id INTEGER, value TEXT, amount REAL, data BLOB, flag INTEGER)'
        );

        $connection->query(
            "INSERT INTO test_types VALUES (1, 'test', 99.99, X'48656C6C6F', 1)"
        );

        $result = $connection->query('SELECT * FROM test_types');

        $row = $result->fetchRow();

        $this->assertSame(1, $row['id']);
        $this->assertSame('test', $row['value']);
        $this->assertSame(99.99, $row['amount']);
        $this->assertSame(1, $row['flag']);

        $definitions = $result->getColumnDefinitions();
        $this->assertEquals(SqliteDataType::Integer, $definitions[0]->getType());
        $this->assertEquals(SqliteDataType::Text, $definitions[1]->getType());
        $this->assertEquals(SqliteDataType::Real, $definitions[2]->getType());
        $this->assertEquals(SqliteDataType::Blob, $definitions[3]->getType());

        $connection->close();
    }

    /**
     * @test
     */
    public function it_can_handle_null_values(): void
    {
        $connection = $this->getConnection();

        $connection->query('CREATE TABLE test_null (id INTEGER, value TEXT)');
        $connection->query('INSERT INTO test_null (id, value) VALUES (1, NULL)');

        $result = $connection->query('SELECT * FROM test_null');

        $row = $result->fetchRow();

        $this->assertSame(1, $row['id']);
        $this->assertNull($row['value']);

        $connection->close();
    }

    /**
     * @test
     */
    public function it_updates_last_used_timestamp_after_query(): void
    {
        $connection = $this->getConnection();

        $initialTime = $connection->getLastUsedAt();

        sleep(1);

        $connection->query('SELECT 1');

        $this->assertGreaterThan($initialTime, $connection->getLastUsedAt());

        $connection->close();
    }

    /**
     * @test
     */
    public function it_can_execute_aggregate_functions(): void
    {
        $connection = $this->getConnection();

        $connection->query('CREATE TABLE test_agg (value INTEGER)');
        $connection->query('INSERT INTO test_agg VALUES (10), (20), (30), (40), (50)');

        $result = $connection->query(
            'SELECT COUNT(*) as count, SUM(value) as sum, AVG(value) as avg, MIN(value) as min, MAX(value) as max FROM test_agg'
        );

        $row = $result->fetchRow();

        $this->assertSame(5, $row['count']);
        $this->assertSame(150, $row['sum']);
        $this->assertSame(30.0, $row['avg']);
        $this->assertSame(10, $row['min']);
        $this->assertSame(50, $row['max']);

        $connection->close();
    }

    /**
     * @test
     */
    public function it_waits_when_connection_is_busy(): void
    {
        $connection = $this->getConnection();

        $reflection = new ReflectionClass($connection);
        $busyProperty = $reflection->getProperty('busy');
        $busyProperty->setAccessible(true);

        $deferred = new DeferredFuture();
        $busyProperty->setValue($connection, $deferred);

        $start = microtime(true);

        $future = async(function () use ($connection) {
            return $connection->query('SELECT 1');
        });

        delay(0.5);

        $deferred->complete();
        $busyProperty->setValue($connection, null);

        $result = $future->await();
        $elapsed = microtime(true) - $start;

        $this->assertGreaterThanOrEqual(0.5, $elapsed, 'Query did not wait for busy connection to be released');
        $this->assertSame(1, $result->fetchRow()[1]);

        $connection->close();
    }
}
