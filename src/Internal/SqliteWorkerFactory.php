<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal;

use Amp\Parallel\Worker\ContextWorkerFactory;
use Amp\Parallel\Worker\Worker;

class SqliteWorkerFactory
{
    private static ContextWorkerFactory|null $factory = null;

    private static Worker|null $worker = null;

    public static function create(): Worker
    {
        if (self::$worker === null) {
            if (self::$factory === null) {
                self::$factory = new ContextWorkerFactory(
                    bootstrapPath: __DIR__ . '/../bootstrap/sqlite-worker.php'
                );
            }

            self::$worker = self::$factory->create();
        }

        return self::$worker;
    }
}
