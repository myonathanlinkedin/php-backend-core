<?php
declare(strict_types=1);

/**
 * Represents an immutable Sorted String Table (SSTable) in an LSM-Tree.
 * The data is stored as a sorted associative array: key => value.
 */
final class SSTable
{
    /** @var int Generation identifier (monotonically increasing) */
    private int $generation;

    /** @var array<string, string> Sorted key-value pairs */
    private array $data;

    /**
     * @param int $generation
     * @param array<string, string> $data  Must be sorted by key ascending.
     */
    public function __construct(int $generation, array $data)
    {
        $this->generation = $generation;
        // Ensure internal data is sorted; keys are strings.
        ksort($data, SORT_STRING);
        $this->data = $data;
    }

    public function getGeneration(): int
    {
        return $this->generation;
    }

    /**
     * @return array<string, string> Sorted key-value pairs.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Retrieve a value for a given key if present.
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return $this->data[$key] ?? null;
    }
}

/**
 * In‑memory mutable sorted map (MemTable) used by the LSM engine.
 * Internally it stores unsorted writes and sorts only on flush.
 */
final class MemTable
{
    /** @var array<string, string> */
    private array $data = [];

    /**
     * Insert or update a key/value pair.
     *
     * @param string $key
     * @param string $value
     */
    public function put(string $key, string $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Retrieve a value for a given key if present.
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Return the number of entries currently stored.
     *
     * @return int
     */
    public function size(): int
    {
        return count($this->data);
    }

    /**
     * Flush the current MemTable into a sorted array suitable for SSTable creation.
     *
     * @return array<string, string> Sorted key-value pairs.
     */
    public function flushData(): array
    {
        $sorted = $this->data;
        ksort($sorted, SORT_STRING);
        return $sorted;
    }

    /**
     * Reset the MemTable to an empty state after a successful flush.
     */
    public function reset(): void
    {
        $this->data = [];
    }
}
