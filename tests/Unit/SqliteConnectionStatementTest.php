<?php

declare(strict_types=1);

use Phenix\Sqlite\Constants\SqliteDataType;
use Phenix\Sqlite\Internal\ConnectionProcessor;
use Phenix\Sqlite\Internal\SqliteConnectionStatement;
use Phenix\Sqlite\SqliteColumnDefinition;
use PHPUnit\Framework\TestCase;

class SqliteConnectionStatementTest extends TestCase
{
    private function makeStatement(array $columnDefs = [], int $paramCount = 2, string $sql = 'SELECT * FROM users WHERE id = ?')
    {
        $processor = $this->createMock(ConnectionProcessor::class);

        return new SqliteConnectionStatement(
            processor: $processor,
            sql: $sql,
            parameterCount: $paramCount,
            columnDefinitions: $columnDefs,
        );
    }

    /** @test */
    public function it_bind_and_reset(): void
    {
        $stmt = $this->makeStatement();
        $stmt->bind(0, 'foo');
        $stmt->bind(1, 'bar');
        $stmt->reset();
        $this->assertTrue(true); // No exception means pass
    }

    /** @test */
    public function it_bind_invalid_index(): void
    {
        $stmt = $this->makeStatement();
        $this->expectException(Error::class);
        $stmt->bind(2, 'fail');
    }

    /** @test */
    public function it_bind_invalid_name(): void
    {
        $stmt = $this->makeStatement();
        $this->expectException(Error::class);
        $stmt->bind('badname', 'fail');
    }

    /** @test */
    public function it_get_column_definitions(): void
    {
        $col = new SqliteColumnDefinition('users', 'id', 11, SqliteDataType::Integer, 0, 0);
        $stmt = $this->makeStatement([$col]);
        $this->assertSame([$col], $stmt->getColumnDefinitions());
    }

    /** @test */
    public function it_get_parameter_definitions(): void
    {
        $stmt = $this->makeStatement([], 3);
        $this->assertSame([null, null, null], $stmt->getParameterDefinitions());
    }

    /** @test */
    public function it_get_query_and_last_used_at(): void
    {
        $stmt = $this->makeStatement([], 2, 'SELECT 1');
        $this->assertSame('SELECT 1', $stmt->getQuery());
        $this->assertIsInt($stmt->getLastUsedAt());
    }

    /** @test */
    public function it_is_closed_and_close(): void
    {
        $stmt = $this->makeStatement();
        $this->assertFalse($stmt->isClosed());
        $stmt->close();
        $this->assertTrue($stmt->isClosed());
    }

    /** @test */
    public function it_on_close_noop(): void
    {
        $stmt = $this->makeStatement();
        $stmt->onClose(fn () => null);
        $this->assertTrue(true); // No exception
    }

    /** @test */
    public function it_execute_when_closed_throws(): void
    {
        $stmt = $this->makeStatement();
        $stmt->close();
        $this->expectException(Error::class);
        $stmt->execute();
    }
}
