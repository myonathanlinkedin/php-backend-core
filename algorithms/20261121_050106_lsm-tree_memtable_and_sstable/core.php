<?php
declare(strict_types=1);

class MemTable
{
    private array $data = []; // key => ?string (null = tombstone)
    private int $capacity;

    public function __construct(int $capacity)
    {
        $this->capacity = $capacity;
    }

    public function put(string $key, string $value): void
    {
        $this->data[$key] = $value;
    }

    public function delete(string $key): void
    {
        $this->data[$key] = null;
    }

    public function get(string $key): ?string
    {
        return $this->data[$key] ?? null;
    }

    public function size(): int
    {
        return count($this->data);
    }

    public function isFull(): bool
    {
        return $this->size() >= $this->capacity;
    }

    /** @return array<int, array{key:string,value:?string}> */
    public function flush(): array
    {
        $entries = [];
        foreach ($this->data as $k => $v) {
            $entries[] = ['key' => $k, 'value' => $v];
        }
        usort($entries, fn($a, $b) => strcmp($a['key'], $b['key']));
        $this->data = [];
        return $entries;
    }
}

class SSTable
{
    /** @var array<int, array{key:string,value:?string}> */
    private array $entries;

    /**
     * @param array<int, array{key:string,value:?string}> $entries Sorted by key.
     */
    public function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    public function get(string $key): ?string
    {
        $low = 0;
        $high = count($this->entries) - 1;
        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            $midKey = $this->entries[$mid]['key'];
            $cmp = strcmp($key, $midKey);
            if ($cmp === 0) {
                return $this->entries[$mid]['value'];
            }
            if ($cmp < 0) {
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }
        return null;
    }

    /** @return array<int, array{key:string,value:?string}> */
    public function getEntries(): array
    {
        return $this->entries;
    }
}

class LSMTreeEngine
{
    private MemTable $memTable;
    /** @var array<int, SSTable[]> $levels */
    private array $levels = [];
    private int $memCapacity;
    private int $sstableThreshold;

    public function __construct(int $memCapacity = 4, int $sstableThreshold = 2)
    {
        $this->memCapacity = $memCapacity;
        $this->sstableThreshold = $sstableThreshold;
        $this->memTable = new MemTable($memCapacity);
    }

    public function put(string $key, string $value): void
    {
        $this->memTable->put($key, $value);
        if ($this->memTable->isFull()) {
            $this->flushMemTable();
        }
    }

    public function delete(string $key): void
    {
        $this->memTable->delete($key);
        if ($this->memTable->isFull()) {
            $this->flushMemTable();
        }
    }

    public function get(string $key): ?string
    {
        $val = $this->memTable->get($key);
        if ($val !== null) {
            return $val;
        }
        // Search from newest level to oldest
        for ($lvl = count($this->levels) - 1; $lvl >= 0; $lvl--) {
            foreach (array_reverse($this->levels[$lvl]) as $sstable) {
                $val = $sstable->get($key);
                if ($val !== null) {
                    return $val;
                }
            }
        }
        return null;
    }

    private function flushMemTable(): void
    {
        $entries = $this->memTable->flush();
        $sstable = new SSTable($entries);
        $this->addSSTableToLevel(0, $sstable);
    }

    private function addSSTableToLevel(int $levelIdx, SSTable $sstable): void
    {
        $this->levels[$levelIdx] ??= [];
        $this->levels[$levelIdx][] = $sstable;
        if (count($this->levels[$levelIdx]) > $this->sstableThreshold) {
            $this->compactLevel($levelIdx);
        }
    }

    private function compactLevel(int $levelIdx): void
    {
        $tables = $this->levels[$levelIdx];
        $merged = $this->mergeTables($tables);
        $this->levels[$levelIdx] = [];
        $this->addSSTableToLevel($levelIdx + 1, $merged);
    }

    /** @param SSTable[] $tables */
    private function mergeTables(array $tables): SSTable
    {
        // Prepare pointers
        $pointers = [];
        foreach ($tables as $i => $t) {
            $entries = $t->getEntries();
            $pointers[$i] = ['entries' => $entries, 'pos' => 0];
        }

        $result = [];
        while (true) {
            $minKey = null;
            $minIdx = null;
            // Find smallest key among current positions
            foreach ($pointers as $idx => $state) {
                $pos = $state['pos'];
                $entries = $state['entries'];
                if ($pos >= count($entries)) {
                    continue;
                }
                $key = $entries[$pos]['key'];
                if ($minKey === null || strcmp($key, $minKey) < 0) {
                    $minKey = $key;
                    $minIdx = $idx;
                }
            }
            if ($minKey === null) {
                break; // all exhausted
            }

            // Resolve value: later tables (newer) overwrite earlier ones
            $value = null;
            foreach ($pointers as $idx => &$state) {
                $pos = $state['pos'];
                $entries = $state['entries'];
                if ($pos < count($entries) && $entries[$pos]['key'] === $minKey) {
                    $value = $entries[$pos]['value'];
                    $state['pos']++;
                }
            }
            unset($state);

            if ($value !== null) {
                $result[] = ['key' => $minKey, 'value' => $value];
            }
        }
        return new SSTable($result);
    }

    // ----- Helpers for tests -----
    public function getMemTableSize(): int
    {
        return $this->memTable->size();
    }

    public function getLevelCount(): int
    {
        return count($this->levels);
    }

    public function getSSTableCountAtLevel(int $levelIdx): int
    {
        return $this->levels[$levelIdx] ?? 0 ? count($this->levels[$levelIdx]) : 0;
    }
}
