<?php

declare(strict_types=1);

namespace Tests\Unit;

use Error;
use Phenix\Sqlite\Constants\SqliteJournalMode;
use Phenix\Sqlite\Constants\SqliteSynchronous;
use Phenix\Sqlite\SqliteConfig;
use Tests\TestCase;

class SqliteConfigTest extends TestCase
{
    /** @test */
    public function it_creates_config_from_path(): void
    {
        $config = SqliteConfig::fromPath('/tmp/test.db');

        $this->assertInstanceOf(SqliteConfig::class, $config);
        $this->assertSame('/tmp/test.db', $config->getPath());
        $this->assertSame('sqlite:/tmp/test.db', $config->getConnectionString());
        $this->assertSame(SqliteConfig::DEFAULT_OPEN_FLAGS, $config->getOpenFlags());
        $this->assertSame(SqliteConfig::DEFAULT_BUSY_TIMEOUT, $config->getBusyTimeout());
        $this->assertSame(SqliteJournalMode::Wal, $config->getJournalMode());
        $this->assertSame(SqliteSynchronous::Normal, $config->getSynchronous());
        $this->assertTrue($config->getForeignKeys());
        $this->assertNull($config->getCacheSize());
    }

    /** @test */
    public function it_creates_config_from_string(): void
    {
        $str = 'path=/tmp/foo.db;flags=3;timeout=1234;journal=delete;sync=full;fk=off;cache=2048';
        $config = SqliteConfig::fromString($str);

        $this->assertSame('/tmp/foo.db', $config->getPath());
        $this->assertSame(3, $config->getOpenFlags());
        $this->assertSame(1234, $config->getBusyTimeout());
        $this->assertSame(SqliteJournalMode::Delete, $config->getJournalMode());
        $this->assertSame(SqliteSynchronous::Full, $config->getSynchronous());
        $this->assertFalse($config->getForeignKeys());
        $this->assertSame(2048, $config->getCacheSize());
    }

    /** @test */
    public function it_throws_if_path_missing_in_string(): void
    {
        $this->expectException(Error::class);
        SqliteConfig::fromString('flags=1');
    }

    /** @test */
    public function it_can_set_open_flags(): void
    {
        $config = SqliteConfig::fromPath('/tmp/a.db');
        $new = $config->withOpenFlags(7);

        $this->assertNotSame($config, $new);
        $this->assertSame(7, $new->getOpenFlags());
    }

    /** @test */
    public function it_can_set_busy_timeout(): void
    {
        $config = SqliteConfig::fromPath('/tmp/b.db');
        $new = $config->withBusyTimeout(9999);

        $this->assertNotSame($config, $new);
        $this->assertSame(9999, $new->getBusyTimeout());
    }

    /** @test */
    public function it_can_set_journal_mode(): void
    {
        $config = SqliteConfig::fromPath('/tmp/c.db');
        $new = $config->withJournalMode(SqliteJournalMode::Memory);

        $this->assertNotSame($config, $new);
        $this->assertSame(SqliteJournalMode::Memory, $new->getJournalMode());
    }

    /** @test */
    public function it_can_set_synchronous(): void
    {
        $config = SqliteConfig::fromPath('/tmp/d.db');
        $new = $config->withSynchronous(SqliteSynchronous::Off);

        $this->assertNotSame($config, $new);
        $this->assertSame(SqliteSynchronous::Off, $new->getSynchronous());
    }

    /** @test */
    public function it_can_set_foreign_keys(): void
    {
        $config = SqliteConfig::fromPath('/tmp/e.db');
        $new = $config->withForeignKeys(false);

        $this->assertNotSame($config, $new);
        $this->assertFalse($new->getForeignKeys());
    }

    /** @test */
    public function it_can_set_cache_size(): void
    {
        $config = SqliteConfig::fromPath('/tmp/f.db');
        $new = $config->withCacheSize(123);

        $this->assertNotSame($config, $new);
        $this->assertSame(123, $new->getCacheSize());
    }
}
