<?php

declare(strict_types=1);

namespace Phenix\Sqlite\Internal;

use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Parallel\Worker\ContextWorkerFactory;
use Amp\Parallel\Worker\Worker;
use SplObjectStorage;

/**
 * Pool of workers for executing SQLite tasks.
 * Manages shared workers for normal queries and dedicated workers for transactions.
 */
class SqliteWorkerPool
{
    use ForbidCloning;
    use ForbidSerialization;

    private readonly ContextWorkerFactory $factory;

    /** @var list<Worker> */
    private array $availableWorkers = [];

    /** @var SplObjectStorage<Worker, true> */
    private readonly SplObjectStorage $allWorkers;

    private int $minPoolSize = 1;
    private int $maxPoolSize = 10;

    public function __construct(string|null $bootstrapPath = null)
    {
        $this->factory = new ContextWorkerFactory(
            bootstrapPath: $bootstrapPath ?? __DIR__ . '/sqlite-worker.php'
        );

        $this->allWorkers = new SplObjectStorage();
    }

    /**
     * Acquire a worker from the pool for general use.
     * Workers acquired this way should be released back to the pool.
     */
    public function acquire(): Worker
    {
        if (empty($this->availableWorkers)) {
            return $this->createWorker();
        }

        return array_pop($this->availableWorkers);
    }

    /**
     * Release a worker back to the pool.
     */
    public function release(Worker $worker): void
    {
        // Only accept workers that belong to this pool
        if (!$this->allWorkers->contains($worker)) {
            return;
        }

        // Don't add back if we're over the max pool size
        if (count($this->availableWorkers) >= $this->maxPoolSize) {
            $this->allWorkers->detach($worker);
            return;
        }

        // Avoid duplicates
        if (!in_array($worker, $this->availableWorkers, true)) {
            $this->availableWorkers[] = $worker;
        }
    }

    /**
     * Acquire a dedicated worker that won't be returned to the shared pool.
     * Use this for transactions to ensure isolation.
     */
    public function acquireDedicated(): Worker
    {
        $worker = $this->createWorker();
        // Mark as dedicated by not tracking in available workers
        return $worker;
    }

    /**
     * Release a dedicated worker. It can optionally be added back to the pool.
     */
    public function releaseDedicated(Worker $worker): void
    {
        if ($this->allWorkers->contains($worker)) {
            $this->allWorkers->detach($worker);
        }

        // Optionally add to pool if under max size
        if (count($this->availableWorkers) < $this->maxPoolSize) {
            $this->availableWorkers[] = $worker;
            $this->allWorkers->attach($worker);
        }
    }

    /**
     * Get current pool statistics.
     *
     * @return array{total: int, available: int, busy: int}
     */
    public function getStats(): array
    {
        $total = $this->allWorkers->count();
        $available = count($this->availableWorkers);

        return [
            'total' => $total,
            'available' => $available,
            'busy' => $total - $available,
        ];
    }

    /**
     * Create a new worker and register it with the pool.
     */
    private function createWorker(): Worker
    {
        $worker = $this->factory->create();
        $this->allWorkers->attach($worker);

        return $worker;
    }

    /**
     * Shutdown all workers in the pool.
     */
    public function shutdown(): void
    {
        $this->availableWorkers = [];
        $this->allWorkers->removeAll($this->allWorkers);
    }
}
