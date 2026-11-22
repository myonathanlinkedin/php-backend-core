<?php
declare(strict_types=1);

/**
 * Cuckoo Filter implementation with deletion support.
 * Uses simple CRC32 based hashing and MD5 for fingerprint generation.
 */
final class CuckooFilter
{
    /** @var array<int, array<string>> Buckets storing fingerprints */
    private array $buckets;

    /** @var int Number of buckets (capacity) */
    private int $capacity;

    /** @var int Number of entries per bucket */
    private int $bucketSize;

    /** @var int Fingerprint length in bytes */
    private int $fingerprintSize;

    /** @var int Maximum number of relocation attempts during insertion */
    private int $maxKicks;

    /** @var int Current number of stored items */
    private int $size = 0;

    /**
     * @param int $capacity Number of buckets (should be > 0)
     * @param int $bucketSize Entries per bucket (default 4)
     * @param int $fingerprintSize Fingerprint length in bytes (default 1)
     * @param int $maxKicks Maximum relocation attempts (default 500)
     */
    public function __construct(int $capacity, int $bucketSize = 4, int $fingerprintSize = 1, int $maxKicks = 500)
    {
        if ($capacity <= 0) {
            throw new InvalidArgumentException('Capacity must be positive');
        }
        if ($bucketSize <= 0) {
            throw new InvalidArgumentException('Bucket size must be positive');
        }
        if ($fingerprintSize <= 0) {
            throw new InvalidArgumentException('Fingerprint size must be positive');
        }
        $this->capacity = $capacity;
        $this->bucketSize = $bucketSize;
        $this->fingerprintSize = $fingerprintSize;
        $this->maxKicks = $maxKicks;
        $this->buckets = array_fill(0, $capacity, []);
    }

    /**
     * Insert an item into the filter.
     *
     * @param string $item
     * @return bool True on success, false if filter is full.
     */
    public function insert(string $item): bool
    {
        $fp = $this->fingerprint($item);
        $i1 = $this->indexHash($item);
        $i2 = $this->altIndex($i1, $fp);

        if ($this->bucketInsert($i1, $fp) || $this->bucketInsert($i2, $fp)) {
            $this->size++;
            return true;
        }

        $index = random_int(0, 1) === 0 ? $i1 : $i2;
        $curFp = $fp;

        for ($kick = 0; $kick < $this->maxKicks; $kick++) {
            $bucket = &$this->buckets[$index];
            $evictPos = random_int(0, count($bucket) - 1);
            $evicted = $bucket[$evictPos];
            $bucket[$evictPos] = $curFp;
            $curFp = $evicted;
            $index = $this->altIndex($index, $curFp);

            if ($this->bucketInsert($index, $curFp)) {
                $this->size++;
                return true;
            }
        }

        // Insertion failed after maxKicks
        return false;
    }

    /**
     * Check whether an item is possibly in the filter.
     *
     * @param string $item
     * @return bool
     */
    public function contains(string $item): bool
    {
        $fp = $this->fingerprint($item);
        $i1 = $this->indexHash($item);
        $i2 = $this->altIndex($i1, $fp);
        return in_array($fp, $this->buckets[$i1], true) || in_array($fp, $this->buckets[$i2], true);
    }

    /**
     * Delete an item from the filter.
     *
     * @param string $item
     * @return bool True if an entry was removed, false otherwise.
     */
    public function delete(string $item): bool
    {
        $fp = $this->fingerprint($item);
        $i1 = $this->indexHash($item);
        $i2 = $this->altIndex($i1, $fp);

        if ($this->bucketDelete($i1, $fp) || $this->bucketDelete($i2, $fp)) {
            $this->size--;
            return true;
        }
        return false;
    }

    /**
     * Current number of stored items.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Load factor (items / (capacity * bucketSize)).
     *
     * @return float
     */
    public function loadFactor(): float
    {
        return $this->size / ($this->capacity * $this->bucketSize);
    }

    /**
     * Reset the filter to empty state.
     */
    public function clear(): void
    {
        $this->buckets = array_fill(0, $this->capacity, []);
        $this->size = 0;
    }

    /** Helper: attempt to insert fingerprint into bucket if space exists. */
    private function bucketInsert(int $index, string $fp): bool
    {
        $bucket = &$this->buckets[$index];
        if (count($bucket) < $this->bucketSize) {
            $bucket[] = $fp;
            return true;
        }
        return false;
    }

    /** Helper: delete fingerprint from bucket if present. */
    private function bucketDelete(int $index, string $fp): bool
    {
        $bucket = &$this->buckets[$index];
        $pos = array_search($fp, $bucket, true);
        if ($pos !== false) {
            array_splice($bucket, $pos, 1);
            return true;
        }
        return false;
    }

    /** Compute fingerprint of an item (binary string of configured length). */
    private function fingerprint(string $item): string
    {
        $hash = md5($item, true); // raw binary
        return substr($hash, 0, $this->fingerprintSize);
    }

    /** Primary bucket index from item hash. */
    private function indexHash(string $item): int
    {
        $h = crc32($item);
        // Ensure unsigned int
        $h = $h & 0xffffffff;
        return $h % $this->capacity;
    }

    /** Alternate bucket index given one index and fingerprint. */
    private function altIndex(int $index, string $fp): int
    {
        $h = crc32($fp);
        $h = $h & 0xffffffff;
        return ($index ^ $h) % $this->capacity;
    }
}
