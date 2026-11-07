<?php
declare(strict_types=1);
require_once 'types.php';

class MemTable implements KeyValueStore {
    /** @var array<string, Entry> */
    private array $map = [];
    private int $maxEntries;
    private int $nextSeq = 0;

    public function __construct(int $maxEntries = 4) {
        $this->maxEntries = $maxEntries;
    }

    public function put(string $key, string $value): void {
        $this->map[$key] = new Entry($key, $value, $this->nextSeq++);
    }

    public function delete(string $key): void {
        $this->map[$key] = new Entry($key, null, $this->nextSeq++);
    }

    public function get(string $key): ?string {
        return $this->map[$key]->value ?? null;
    }

    public function size(): int {
        return count($this->map);
    }

    public function isFull(): bool {
        return $this->size() >= $this->maxEntries;
    }

    /** @return SSTable */
    public function flush(): SSTable {
        $entries = array_values($this->map);
        usort($entries, function (Entry $a, Entry $b): int {
            $cmp = strcmp($a->key, $b->key);
            if ($cmp !== 0) {
                return $cmp;
            }
            // newer sequence first
            return $b->seq <=> $a->seq;
        });

        $deduped = [];
        $seen    = [];
        foreach ($entries as $e) {
            if (!isset($seen[$e->key])) {
                $deduped[] = $e;
                $seen[$e->key] = true;
            }
        }

        $this->map = []; // reset memtable
        return new SSTable($deduped);
    }
}

class SSTable {
    /** @var Entry[] sorted by key */
    private array $entries;

    public function __construct(array $entries) {
        $this->entries = $entries;
    }

    public function get(string $key): ?string {
        $low  = 0;
        $high = count($this->entries) - 1;
        while ($low <= $high) {
            $mid    = intdiv($low + $high, 2);
            $midKey = $this->entries[$mid]->key;
            $cmp    = strcmp($key, $midKey);
            if ($cmp === 0) {
                return $this->entries[$mid]->value;
            } elseif ($cmp < 0) {
                $high = $mid - 1;
            } else {
                $low = $mid + 1;
            }
        }
        return null;
    }

    /** @return Entry[] */
    public function getEntries(): array {
        return $this->entries;
    }
}

class LSMEngine implements KeyValueStore {
    private MemTable $memTable;
    /** @var array<int, SSTable[]> level => list of tables */
    private array $levels = [];
    private int $memTableMaxEntries;
    private int $levelMaxTables;

    public function __construct(int $memTableMaxEntries = 4, int $levelMaxTables = 2) {
        $this->memTableMaxEntries = $memTableMaxEntries;
        $this->levelMaxTables     = $levelMaxTables;
        $this->memTable           = new MemTable($this->memTableMaxEntries);
    }

    public function put(string $key, string $value): void {
        $this->memTable->put($key, $value);
        $this->maybeFlush();
    }

    public function delete(string $key): void {
        $this->memTable->delete($key);
        $this->maybeFlush();
    }

    public function get(string $key): ?string {
        $val = $this->memTable->get($key);
        if ($val !== null) {
            return $val;
        }
        foreach ($this->levels as $levelTables) {
            foreach ($levelTables as $sst) {
                $val = $sst->get($key);
                if ($val !== null) {
                    return $val;
                }
            }
        }
        return null;
    }

    private function maybeFlush(): void {
        if ($this->memTable->isFull()) {
            $sst = $this->memTable->flush();
            $this->addSSTable(0, $sst);
        }
    }

    private function addSSTable(int $level, SSTable $sst): void {
        if (!isset($this->levels[$level])) {
            $this->levels[$level] = [];
        }
        $this->levels[$level][] = $sst;
        if (count($this->levels[$level]) > $this->levelMaxTables) {
            $this->compactLevel($level);
        }
    }

    private function compactLevel(int $level): void {
        $tables = $this->levels[$level];
        $merged = $this->mergeTables($tables);
        $this->levels[$level] = [];
        $this->addSSTable($level + 1, $merged);
    }

    /** @param SSTable[] $tables */
    private function mergeTables(array $tables): SSTable {
        if (count($tables) === 0) {
            return new SSTable([]);
        }
        $merged = $tables[0];
        for ($i = 1, $n = count($tables); $i < $n; $i++) {
            $merged = $this->mergeTwo($merged, $tables[$i]);
        }
        return $merged;
    }

    private function mergeTwo(SSTable $a, SSTable $b): SSTable {
        $entriesA = $a->getEntries();
        $entriesB = $b->getEntries();
        $i = 0;
        $j = 0;
        $result = [];
        $seen   = [];

        while ($i < count($entriesA) && $j < count($entriesB)) {
            $eA = $entriesA[$i];
            $eB = $entriesB[$j];
            $cmp = strcmp($eA->key, $eB->key);
            if ($cmp === 0) {
                $chosen = ($eA->seq >= $eB->seq) ? $eA : $eB;
                if (!isset($seen[$chosen->key])) {
                    $result[] = $chosen;
                    $seen[$chosen->key] = true;
                }
                $i++; $j++;
            } elseif ($cmp < 0) {
                if (!isset($seen[$eA->key])) {
                    $result[] = $eA;
                    $seen[$eA->key] = true;
                }
                $i++;
            } else {
                if (!isset($seen[$eB->key])) {
                    $result[] = $eB;
                    $seen[$eB->key] = true;
                }
                $j++;
            }
        }
        while ($i < count($entriesA)) {
            $eA = $entriesA[$i];
            if (!isset($seen[$eA->key])) {
                $result[] = $eA;
                $seen[$eA->key] = true;
            }
            $i++;
        }
        while ($j < count($entriesB)) {
            $eB = $entriesB[$j];
            if (!isset($seen[$eB->key])) {
                $result[] = $eB;
                $seen[$eB->key] = true;
            }
            $j++;
        }
        return new SSTable($result);
    }

    // ----- Helpers for unit tests -----
    public function getMemTableSize(): int {
        return $this->memTable->size();
    }

    /** @return array<int, int> level => table count */
    public function getLevelTableCounts(): array {
        $counts = [];
        foreach ($this->levels as $lvl => $tables) {
            $counts[$lvl] = count($tables);
        }
        return $counts;
    }
}
