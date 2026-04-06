---
applyTo: '**'
---
Provide project context and coding guidelines that AI should follow when generating code, answering questions, or reviewing changes.

This project is a PHP library for interacting with SQLite databases using asynchronous programming principles. It builds upon the Amp framework to provide non-blocking database operations.

When generating code, please adhere to the following guidelines:

1. **Asynchronous Programming**: Ensure that all database operations are non-blocking and utilize Amp's concurrency features effectively.
2. **Type Safety**: Use strict typing and appropriate type hints for all functions and methods. Leverage PHPDoc annotations for complex types where necessary.
3. **Error Handling**: Implement robust error handling for database operations, ensuring that exceptions are caught and managed appropriately.
4. **Code Style**: Follow PSR-12 coding standards for PHP code style and formatting.
5. **Documentation**: Provide clear and concise documentation for all public interfaces, classes, and methods, explaining their purpose and usage.
6. **Testing**: Write comprehensive unit and integration tests to ensure the reliability and correctness of the library's functionality. Use PHPUnit for testing.
7. **Performance**: Optimize database queries and operations for performance, minimizing latency and resource usage.

## Development guide

- Use /knowledge/amphp-file as a reference implementation of parallel processing for files with Amp. Check code examples in knowledge/amphp-file/examples, check test cases in knowledge/amphp-file/tests.
- Use /knowledge/amphp-parallel as a reference implementation of parallel processing with Amp. Check code examples in knowledge/amphp-parallel/examples, check test cases in knowledge/amphp-parallel/tests.
- Use /knowledge/amphp-mysql as a reference implementation of a database library built on Amp, but adapted for SQLite. Check code examples in knowledge/amphp-mysql/examples, check test cases in knowledge/amphp-mysql/tests.
- Use Amp documentation for async programming patterns and best practices.
- Use PDO SQLite extension documentation for SQLite-specific SQL syntax and features.
- Use PHP 8.2 features and syntax where applicable.
- No exists async and await keywords in PHP, use Amp's concurrency primitives instead. Example:
- No use final keyword for classes.
- Use PHPStan for static analysis and type checking.
- Add dockblocks only when it is required by PHPStan.
- Use strict types in all PHP files.
- Use union type with null (Type|null) instead of shorthand Nullable Type (?Type).

## Goal of the project

The goal of this project is to provide a high-performance, asynchronous SQLite client for PHP applications. By leveraging the Amp framework, the library aims to facilitate non-blocking database interactions, enabling developers to build scalable and efficient applications that can handle concurrent database operations seamlessly.

MySQL uses Socket connections over the network, while SQLite is a file-based database. This library adapts the patterns and abstractions from `amphp/mysql` to work with SQLite's architecture, providing a familiar interface for developers accustomed to MySQL while optimizing for SQLite's unique characteristics.

Then, we need to use the amphp/file strategy to handle file access asynchronously by using amphp/parallel to manage multiple connections or operations in parallel when needed. amphp/file has two drivers: Parallel and Blocking, we should implementuse the Parallel driver only for better performance.

## Links

- [Amp Framework](https://amphp.org/)
- [Amp File Docs](https://amphp.org/file/)
- [Amp MySQL Docs](https://amphp.org/mysql/)
- [Amp Parallel Docs](https://amphp.org/parallel/)
- [PHP 8.2 Documentation](https://www.php.net/releases/8.2/en.php)
- [PDO SQLite Documentation](https://www.php.net/manual/en/ref.pdo-sqlite.php)
- [SQLite Documentation](https://www.sqlite.org/docs.html)

## Snippets

### Async and await with Amp

```php
<?php // hello-world.php

require __DIR__ . '/vendor/autoload.php';

use Amp\Future;
use function Amp\async;
use function Amp\delay;

$future1 = async(function () {
    echo 'Hello ';

    // delay() is a non-blocking version of PHP's sleep() function,
    // which only pauses the current fiber instead of blocking the whole process.
    delay(2);

    echo 'the future! ';
});

$future2 = async(function () {
    echo 'World ';

    // Let's pause for only 1 instead of 2 seconds here,
    // so our text is printed in the correct order.
    delay(1);

    echo 'from ';
});

// Our functions have been queued, but won't be executed until the event-loop gains control.
echo "Let's start: ";

// Awaiting a future outside a fiber switches to the event loop until the future is complete.
// Once the event loop gains control, it executes our already queued functions we've passed to async()
$future1->await();
$future2->await();

echo PHP_EOL;
```

### Parallel Processing with Amp

#### Basic usage

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Amp\Future;
use Amp\Parallel\Worker;
use function Amp\async;

$urls = [
    'https://secure.php.net',
    'https://amphp.org',
    'https://github.com',
];

$executions = [];
foreach ($urls as $url) {
    // FetchTask is just an example, you'll have to implement
    // the Task interface for your task.
    $executions[$url] = Worker\submit(new FetchTask($url));
}

// Each submission returns an Execution instance to allow two-way
// communication with a task. Here we're only interested in the
// task result, so we use the Future from Execution::getFuture()
$responses = Future\await(array_map(
    fn (Worker\Execution $e) => $e->getFuture(),
    $executions,
));

foreach ($responses as $url => $response) {
    \printf("Read %d bytes from %s\n", \strlen($response), $url);
}
```

#### Task

```php
// FetchTask.php
// Tasks must be defined in a file which can be loaded by the composer autoloader.

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;

class FetchTask implements Task
{
    public function __construct(
        private readonly string $url,
    ) {
    }

    public function run(Channel $channel, Cancellation $cancellation): string
    {
        return file_get_contents($this->url); // Example blocking function
    }
}
```

#### Task executions

```php
// main.php

$worker = Amp\Parallel\Worker\createWorker();
$task = new FetchTask('https://amphp.org');

$execution = $worker->submit($task);

// $data will be the return value from FetchTask::run()
$data = $execution->await();
```

#### Sharing between tasks

```php
use Amp\Cache\LocalCache;
use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;

class ExampleTask implements Task
{
    private static LocalCache|null $cache = null;

    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $cache = self::$cache ??= new LocalCache();
        $cachedValue = $cache->get('cache-key');
        // Use and modify $cachedValue...
        $cache->set('cache-key', $updatedValue);
        return $updatedValue;
    }
}
```
