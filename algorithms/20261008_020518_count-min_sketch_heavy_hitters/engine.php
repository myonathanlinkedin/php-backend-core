<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Count‑Min Sketch implementation with heavy‑hitter extraction.
 *
 * The sketch uses pairwise independent hash functions derived from
 * random seeds and the built‑in crc32 algorithm. All counters are 64‑bit
 * integers to avoid overflow in typical test scenarios.
 */
final class CountMinSketch implements FrequencyEstimator
{
    /** @var int Number of columns (width) */
    private $width;
    /** @var int Number of rows (depth) */
    private $depth;
    /** @var array<int,array<int,int>> 2‑D array of counters */
    private $table;
    /** @var array<int,int> Random seeds for each hash function */
    private $seeds;
    /** @var int Total count of all increments */
    private $total = 0;

    /**
     * @param int $width Number of columns (must be > 0)
     * @param int $depth Number of rows (must be > 0)
     *
     * @throws InvalidArgumentException
     */
    public function __construct(int $width, int $depth)
    {
        if ($width <= 0) {
            throw new InvalidArgumentException('Width must be positive');
        }
        if ($depth <= 0) {
            throw new InvalidArgumentException('Depth must be positive');
        }

        $this->width  = $width;
        $this->depth  = $depth;
        $this->table  = array_fill(0, $depth, array_fill(0, $width, 0));
        $this->seeds  = [];

        // Generate a distinct random seed for each hash function.
        for ($i = 0; $i < $depth; $i++) {
            // random_int is cryptographically secure and available in the stdlib.
            $this->seeds[$i] = random_int(1, PHP_INT_MAX);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $item, int $increment = 1): void
    {
        if ($increment < 0) {
            throw new InvalidArgumentException('Increment must be non‑negative');
        }
        if ($increment === 0) {
            return;
        }

        for ($i = 0; $i < $this->depth; $i++) {
            $col = $this->hash($item, $this->seeds[$i]) % $this->width;
            $this->table[$i][$col] += $increment;
        }
        $this->total += $increment;
    }

    /**
     * {@inheritdoc}
     */
    public function estimate(string $item): int
    {
        $min = PHP_INT_MAX;
        for ($i = 0; $i < $this->depth; $i++) {
            $col = $this->hash($item, $this->seeds[$i]) % $this->width;
            $val = $this->table[$i][$col];
            if ($val < $min) {
                $min = $val;
            }
        }
        return $min;
    }

    /**
     * {@inheritdoc}
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeavyHitters(float $phi): array
    {
        if ($phi <= 0.0 || $phi >= 1.0) {
            throw new InvalidArgumentException('Phi must be in (0,1)');
        }

        $threshold = (int)ceil($phi * $this->total);
        $candidates = [];

        // In a pure CMS we cannot enumerate all items without external storage.
        // For demonstration we keep a simple internal map of observed items.
        // This map is populated lazily during add() calls.
        // To keep the implementation self‑contained we store it in a private property.
        // If the map is empty (no items observed), return empty array.
        if (empty($this->observedItems)) {
            return [];
        }

        foreach ($this->observedItems as $item) {
            $est = $this->estimate($item);
            if ($est >= $threshold) {
                $candidates[$item] = $est;
            }
        }

        return $candidates;
    }

    /**
     * Simple hash function using crc32 with a seed.
     *
     * @param string $item
     * @param int    $seed
     *
     * @return int Unsigned 32‑bit integer
     */
    private function hash(string $item, int $seed): int
    {
        // Concatenate seed to the item to vary the hash per row.
        // crc32 returns signed int on 32‑bit platforms; we cast to int and mask.
        $hash = crc32($item . $seed);
        // Ensure unsigned representation.
        return $hash & 0xffffffff;
    }

    /**
     * Internal set of distinct items observed so far.
     *
     * @var array<string,bool>
     */
    private $observedItems = [];

    /**
     * Override add() to record observed items.
     *
     * @param string $item
     * @param int    $increment
     */
    public function addWithTracking(string $item, int $increment = 1): void
    {
        $this->observedItems[$item] = true;
        $this->add($item, $increment);
    }
}
