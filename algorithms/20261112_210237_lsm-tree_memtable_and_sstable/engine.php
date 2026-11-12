<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Core LSM‑Tree engine handling writes, reads, automatic MemTable flushing,
 * and background compaction of SSTables.
 */
final class LSMEngine
{
    /** @var MemTable */
    private MemTable $memTable;

    /** @var array<int, SSTable>  Sorted by generation ascending (oldest first). */
    private array $sstables = [];

    /** @var int Monotonically increasing generation counter for SSTables. */
    private int $nextGeneration = 1;

    /** @var int Maximum number of entries before an automatic flush. */
    private int $memTableThreshold;

    /** @var int Number of SSTables that trigger a compaction. */
    private int $sstableCompactionThreshold;

    /**
     * @param int $memTableThreshold   Flush when MemTable reaches this size.
     * @param int $sstableCompactionThreshold   Trigger compaction when this many SSTables exist.
     */
    public function __construct(int $memTableThreshold = 4, int $sstableCompactionThreshold = 3)
    {
        $this->memTable = new MemTable();
        $this->memTableThreshold = $memTableThreshold;
        $this->sstableCompactionThreshold = $sstableCompactionThreshold;
    }

    /**
     * Insert or update a key/value pair.
     *
     * @param string $key
     * @param string $value
     */
    public function put(string $key, string $value): void
    {
        $this->memTable->put($key, $value);
        if ($this->memTable->size() >= $this->memTableThreshold) {
            $this->flushMemTable();
        }
    }

    /**
     * Retrieve the most recent value for a key, searching MemTable first,
     * then SSTables from newest to oldest.
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        // Check MemTable (most recent writes)
        $value = $this->memTable->get($key);
        if ($value !== null) {
            return $value;
        }

        // Search SSTables from newest to oldest (higher generation first)
        for ($i = count($this->sstables) - 1; $i >= 0; $i--) {
            $value = $this->sstables[$i]->get($key);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Force a flush of the current MemTable into a new SSTable.
     */
    public function flushMemTable(): void
    {
        $sortedData = $this->memTable->flushData();
        if (empty($sortedData)) {
            // Nothing to flush.
            return;
        }

        $sstable = new SSTable($this->nextGeneration++, $sortedData);
        $this->sstables[] = $sstable;
        $this->memTable->reset();

        // Possibly trigger compaction.
        if (count($this->sstables) >= $this->sstableCompactionThreshold) {
            $this->compactSSTables();
        }
    }

    /**
     * Merge the two oldest SSTables into a single new SSTable.
     * This simple strategy keeps the number of SSTables bounded.
     */
    private function compactSSTables(): void
    {
        // Ensure at least two SSTables exist.
        if (count($this->sstables) < 2) {
            return;
        }

        // Take the two oldest (lowest generation) tables.
        $oldest = $this->sstables[0];
        $secondOldest = $this->sstables[1];

        $merged = $this->mergeTwoSSTables($oldest, $secondOldest);
        $newSSTable = new SSTable($this->nextGeneration++, $merged);

        // Remove the two old tables and append the new one.
        array_shift($this->sstables); // remove oldest
        array_shift($this->sstables); // remove second oldest
        $this->sstables[] = $newSSTable;
    }

    /**
     * Merge two sorted SSTables, preferring newer values (higher generation) on key conflict.
     *
     * @param SSTable $a Older SSTable.
     * @param SSTable $b Newer SSTable.
     * @return array<string, string> Sorted merged data.
     */
    private function mergeTwoSSTables(SSTable $a, SSTable $b): array
    {
        $dataA = $a->getData();
        $dataB = $b->getData();

        $merged = [];
        $i = $j = 0;
        $keysA = array_keys($dataA);
        $keysB = array_keys($dataB);
        $lenA = count($keysA);
        $lenB = count($keysB);

        while ($i < $lenA && $j < $lenB) {
            $keyA = $keysA[$i];
            $keyB = $keysB[$j];
            if ($keyA === $keyB) {
                // Conflict: value from newer SSTable ($b) wins.
                $merged[$keyA] = $dataB[$keyB];
                $i++;
                $j++;
            } elseif ($keyA < $keyB) {
                $merged[$keyA] = $dataA[$keyA];
                $i++;
            } else {
                $merged[$keyB] = $dataB[$keyB];
                $j++;
            }
        }

        // Append remaining entries.
        while ($i < $lenA) {
            $keyA = $keysA[$i];
            $merged[$keyA] = $dataA[$keyA];
            $i++;
        }
        while ($j < $lenB) {
            $keyB = $keysB[$j];
            $merged[$keyB] = $dataB[$keyB];
            $j++;
        }

        // Ensure final ordering.
        ksort($merged, SORT_STRING);
        return $merged;
    }

    /**
     * Expose internal SSTable list for testing/inspection.
     *
     * @return array<int, SSTable>
     */
    public function getSSTables(): array
    {
        return $this->sstables;
    }

    /**
     * Expose the current MemTable for testing.
     *
     * @return MemTable
     */
    public function getMemTable(): MemTable
    {
        return $this->memTable;
    }
}
