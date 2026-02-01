# phenixphp/sqlite - AI Coding Instructions

Asynchronous SQLite client for PHP built on Amp v3 framework. This library adapts patterns from `amphp/mysql` to work with SQLite's file-based architecture.

## Architecture Overview

**Core Components:**
- `SqliteConnection` - Main connection abstraction wrapping `Internal\ConnectionProcessor`
- `SqliteConfig` - File-based configuration (path, open flags, pragmas) instead of network config
- `SqliteColumnDefinition` - Simplified metadata reflecting SQLite's 5 storage classes (NULL, INTEGER, REAL, TEXT, BLOB)
- `SqliteDataType` enum - Type affinity system, NOT MySQL's 30+ types

**Key Design Decisions:**
- SQLite uses **storage classes** not strict types - see `SqliteDataType::fromDeclaredType()` for affinity rules
- No connection pooling needed (file-based, not network) - connection reuse pattern differs from MySQL
- Uses Amp's parallel worker pools for potential multi-connection scenarios (see `amphp/parallel` in `knowledge/`)
- Adapting MySQL protocol patterns to SQLite3 native extensions
- Don't act condescendingly; adopt a technical, critical, and punctual approach.

## Critical Workflows

```bash
# Format code (uses PSR-12 + custom rules)
composer format

# Static analysis (PHPStan level max)
composer analyze

# Run tests (uses Pest, not PHPUnit)
composer test
composer test:parallel  # Uses parallel execution
```

## Code Conventions

**Type Declarations:**
- ALWAYS `Type|null` NEVER `?Type` (enforced by PHP-CS-Fixer `nullable_type_declaration`)
- Blank line after `<?php` before `declare(strict_types=1);`
- No `final` keyword on classes (project standard)
- DocBlocks only when PHPStan requires them

**SQLite-Specific:**
- Type affinity over strict types - `SqliteDataType::Blob` for empty declared types
- `getLastInsertId()` returns `int|null` (supports RETURNING but maintains traditional pattern)
- No charset/collation config (UTF-8 default)
- Configuration uses `path`, `openFlags`, `busyTimeout`, `journalMode`, `synchronous`, `foreignKeys`, `cacheSize`

**Amp Patterns:**
```php
// NO async/await keywords in PHP - use Amp primitives
use function Amp\async;
use function Amp\delay;

$future = async(fn() => $connection->query($sql));
$result = $future->await();
```

## Reference Implementation

Look at `knowledge/amphp-mysql/` for database patterns adapted to SQLite:
- Connection lifecycle management
- Prepared statements with parameter binding
- Result set handling with column definitions
- Transaction isolation levels

Key differences from MySQL:
- No `host`, `port`, `user`, `password` in config
- No compression or network-related flags
- Simpler column metadata (no charset field)
- Different PRAGMA-based configuration vs SQL variables

## Integration Points

- Extends `Amp\Sql\SqlConnection` interface
- Uses `Amp\Parallel\Worker` for potential concurrent file operations
- Leverages `Amp\Parser\Parser` for protocol handling (adapted from MySQL patterns)
- File operations via Amp event loop, not blocking I/O

## Testing

Uses **Pest** (not PHPUnit) - see `composer.json` scripts. Test structure follows Amp conventions with async test cases.
