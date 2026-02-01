# phenixphp/sqlite

<p>Asynchronous SQLite 3 client for PHP based on <a href="https://amphp.org/">Amp</a>.</p>

[![Tests](https://github.com/phenixphp/sqlite/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/phenixphp/sqlite/actions/workflows/run-tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/phenixphp/sqlite.svg?style=flat-square)](https://packagist.org/packages/phenixphp/sqlite)
[![Total Downloads](https://img.shields.io/packagist/dt/phenixphp/sqlite.svg?style=flat-square)](https://packagist.org/packages/phenixphp/sqlite)
[![PHP Version](https://img.shields.io/packagist/php/phenixphp/sqlite.svg?style=flat-square)](https://packagist.org/packages/phenixphp/sqlite)
[![License](https://img.shields.io/packagist/license/phenixphp/sqlite.svg?style=flat-square)](https://packagist.org/packages/phenixphp/sqlite)

---

**phenixphp/sqlite** is part of the **Phenix PHP** framework ecosystem. Phenix is a web framework built on pure PHP, without external extensions, based on the [Amphp](https://amphp.org/) ecosystem, which provides non-blocking operations, asynchronism and parallel code execution natively. It runs in the PHP SAPI CLI and on its own server — it is simply powerful.

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Establishing a Connection](#establishing-a-connection)
  - [Executing Queries](#executing-queries)
  - [Prepared Statements](#prepared-statements)
  - [Transactions](#transactions)
- [Dependencies](#dependencies)
- [License](#license)

---

## Requirements

| Requirement | Version |
| --- | --- |
| PHP | `^8.2` |
| ext-sqlite3 | `*` |

---

## Installation

Install the package via [Composer](https://getcomposer.org/):

```bash
composer require phenixphp/sqlite
```

---

## Configuration

The `SqliteConfig` class is the entry point for configuring the SQLite connection. It allows you to define the database path and connection parameters aligned with the Amphp SQL ecosystem.

```php
<?php

use Phenix\Sqlite\SqliteConfig;

$config = new SqliteConfig(
    database: __DIR__ . '/database.db',
);
```

> You can use `:memory:` as the database path to create an in-memory SQLite database, which is ideal for testing scenarios.

```php
$config = new SqliteConfig(
    database: ':memory:',
);
```

---

## Usage

### Establishing a Connection

Use `SqliteConfig` to create and manage asynchronous connections to your SQLite database. Since this package is built on top of Amphp, all database operations are **non-blocking** and run asynchronously using fibers.

```php
<?php

declare(strict_types=1);

use Phenix\Sqlite\SqliteConfig;

use function Phenix\Sqlite\connect;

$config = new SqliteConfig(
    database: __DIR__ . '/database.db',
);

$connection = connect($config);

// ... use connection ...

$connection->close();
```

---

### Executing Queries

Once you have an active connection, you can execute SQL statements — `CREATE`, `INSERT`, `SELECT`, `UPDATE`, and `DELETE` — all asynchronously without blocking the event loop.

#### Creating a Table

```php
$connection->execute(
    'CREATE TABLE IF NOT EXISTS users (
        id   INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL
    )'
);
```

#### Inserting Records

```php
$connection->execute(
    "INSERT INTO users (name, email) VALUES ('John Doe', 'john@example.com')"
);
```

#### Selecting Records

```php
$result = $connection->query('SELECT * FROM users');

foreach ($result as $row) {
    echo $row['id'] . ': ' . $row['name'] . ' — ' . $row['email'] . PHP_EOL;
}
```

#### Updating Records

```php
$connection->execute(
    "UPDATE users SET name = 'Jane Doe' WHERE id = 1"
);
```

#### Deleting Records

```php
$connection->execute(
    "DELETE FROM users WHERE id = 1"
);
```

---

### Prepared Statements

Prepared statements allow you to precompile a SQL template and execute it repeatedly with different bound parameters. This improves both performance and security by preventing SQL injection.

#### Preparing and Executing a Statement

```php
$statement = $connection->prepare(
    'INSERT INTO users (name, email) VALUES (?, ?)'
);

$statement->execute(['Alice', 'alice@example.com']);
$statement->execute(['Bob',   'bob@example.com']);
```

#### Prepared Statement with Named Parameters

```php
$statement = $connection->prepare(
    'SELECT * FROM users WHERE name = ? AND email = ?'
);

$result = $statement->execute(['Alice', 'alice@example.com']);

foreach ($result as $row) {
    echo $row['name'] . PHP_EOL;
}
```

#### Closing a Prepared Statement

```php
$statement->close();
```

---

### Transactions

Transactions group multiple SQL operations into a single atomic unit. If any operation fails, all changes within the transaction are rolled back, ensuring data consistency.

#### Basic Transaction

```php
$transaction = $connection->beginTransaction();

try {
    $transaction->execute(
        "INSERT INTO users (name, email) VALUES ('Charlie', 'charlie@example.com')"
    );
    $transaction->execute(
        "INSERT INTO users (name, email) VALUES ('Diana', 'diana@example.com')"
    );

    $transaction->commit();
} catch (\Throwable $e) {
    $transaction->rollback();

    throw $e;
}
```

#### Transaction with Prepared Statements

```php
$transaction = $connection->beginTransaction();

try {
    $statement = $transaction->prepare(
        'INSERT INTO users (name, email) VALUES (?, ?)'
    );

    $statement->execute(['Eve',   'eve@example.com']);
    $statement->execute(['Frank', 'frank@example.com']);

    $transaction->commit();
} catch (\Throwable $e) {
    $transaction->rollback();

    throw $e;
}
```

---

## License

This package is open-sourced software licensed under the [MIT](https://opensource.org/licenses/MIT) license.
