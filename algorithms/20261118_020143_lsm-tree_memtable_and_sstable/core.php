<?php
declare(strict_types=1);

class MemTable
{
    private array $data = [];          // key => ['value'=>string,'seq'=>int,'tombstone'=>bool]
    private int $maxSize;
    private int $currentSize = 0;

    public function __construct(int $maxSize)
    {
        $this->maxSize = $maxSize;
    }

    public function put(string $key, string $value, int $seq): void
    {
        $this->data[$key] = ['value' => $value, 'seq' => $seq, 'tombstone' => false];
        $this->currentSize++;
    }

    public function delete(string $key, int $seq): void
    {
        $this->data[$key] = ['value' => '', 'seq' => $seq, 'tombstone' => true];
        $this->currentSize++;
    }

    public function get(string $key): ?array
    {
        return $this->data[$key] ?? null;
    }

    public function isFull(): bool
    {
        return $this->currentSize >= $this->maxSize;
    }

    /** @return array<string,array{value:string,seq:int,tombstone:bool}> */
    public function flush(): array
    {
        $entries = $this->data;
        ksort($entries, SORT_STRING);
        $this->data = [];
        $this->currentSize = 0;
        return $entries;
    }
}

class SSTable
{
    /** @var array<int,array{key:string,value:string,seq:int,tombstone:bool}> */
    private array $entries;

    /**
     * @param array<int,array{key:string,value:string,seq:int,tombstone:bool}> $entries Sorted by key.
     */
    public function __construct(array $entries)
    {
        $this->entries = $entries;
    }

    public function get(string $key): ?array
    {
        $low = 0;
        $high = count($this->entries) - 1;
        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            $midKey = $this->entries[$mid]['key'];
            if ($midKey === $key) {
                return $this->entries[$mid];
            } elseif ($midKey < $key) {
                $low = $mid + 1;
            } else {
                $high = $mid - 1;
            }
        }
        return null;
    }

    /** @return array<int,array{key:string,value:string,seq:int,tombstone:bool}> */
    public function getEntries(): array
    {
        return $this->entries;
    }
}

class LSMTree
{
    private MemTable $mem;
    /** @var array<int,array<int,SSTable>> Level => list of SSTables */
    private array $levels = [];
    private int $maxMemSize;
    private int $seq = 0;
    private int $sstableThreshold = 4; // max SSTables per level before compaction

    public function __construct(int $maxMemSize = 4)
    {
        $this->maxMemSize = $maxMemSize;
        $this->mem = new MemTable($maxMemSize);
    }

    private function nextSeq(): int
    {
        return ++$this->seq;
    }

    public function put(string $key, string $value): void
    {
        $seq = $this->nextSeq();
        $this->mem->put($key, $value, $seq);
        if ($this->mem->isFull()) {
            $this->flushMem();
        }
    }

    public function delete(string $key): void
    {
        $seq = $this->nextSeq();
        $this->mem->delete($key, $seq);
        if ($this->mem->isFull()) {
            $this->flushMem();
        }
    }

    public function get(string $key): ?string
    {
        $memEntry = $this->mem->get($key);
        if ($memEntry !== null) {
            return $memEntry['tombstone'] ? null : $memEntry['value'];
        }

        foreach ($this->levels as $level) {
            foreach ($level as $sst) {
                $entry = $sst->get($key);
                if ($entry !== null) {
                    return $entry['tombstone'] ? null : $entry['value'];
                }
            }
        }
        return null;
    }

    private function flushMem(): void
    {
        $raw = $this->mem->flush(); // associative array keyed by key
        $entries = [];
        foreach ($raw as $key => $info) {
            $entries[] = [
                'key' => $key,
                'value' => $info['value'],
                'seq' => $info['seq'],
                'tombstone' => $info['tombstone'],
            ];
        }
        // $entries already sorted because $raw was ksorted
        $sst = new SSTable($entries);
        $this->addSSTable(0, $sst);
    }

    private function addSSTable(int $level, SSTable $sst): void
    {
        if (!isset($this->levels[$level])) {
            $this->levels[$level] = [];
        }
        $this->levels[$level][] = $sst;
        if (count($this->levels[$level]) > $this->sstableThreshold) {
            $this->compactLevel($level);
        }
    }

    private function compactLevel(int $level): void
    {
        $sstables = $this->levels[$level];
        unset($this->levels[$level]); // remove old level
        $merged = $this->mergeSSTables($sstables);
        $newSST = new SSTable($merged);
        $this->addSSTable($level + 1, $newSST);
    }

    /**
     * @param array<int,SSTable> $sstables
     * @return array<int,array{key:string,value:string,seq:int,tombstone:bool}> Sorted merged entries.
     */
    private function mergeSSTables(array $sstables): array
    {
        $all = [];
        foreach ($sstables as $sst) {
            foreach ($sst->getEntries() as $e) {
                $all[] = $e;
            }
        }
        usort($all, function (array $a, array $b): int {
            if ($a['key'] === $b['key']) {
                // higher sequence number wins
                return $b['seq'] <=> $a['seq'];
            }
            return $a['key'] <=> $b['key'];
        });

        $result = [];
        $prevKey = null;
        foreach ($all as $e) {
            if ($e['key'] !== $prevKey) {
                $result[] = $e;
                $prevKey = $e['key'];
            }
        }
        return $result;
    }

    /** @return array<int,array<int,SSTable>> For testing/inspection */
    public function getLevels(): array
    {
        return $this->levels;
    }
}
