<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal;

use Amp\Parallel\Worker\ContextWorkerFactory;
use Amp\Parallel\Worker\Worker;

class SqliteWorkerFactory
{
    private static ContextWorkerFactory|null $factory = null;

    public static function create(): Worker
    {
        if (self::$factory === null) {
            self::$factory = new ContextWorkerFactory(
                bootstrapPath: __DIR__ . DIRECTORY_SEPARATOR . 'sqlite-worker.php'
            );
        }

        return self::$factory->create();
    }
}
