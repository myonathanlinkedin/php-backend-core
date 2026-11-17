<?php
declare(strict_types=1);

class MemoryException extends \Exception {}

final class MemoryArena
{
    private int $capacity;
    /** @var array<int, int> $memory  byte values 0-255 */
    private array $memory;
    /** @var array<int, array{offset:int,size:int}> $freeList */
    private array $freeList = [];
    /** @var array<int, int> $allocMap offset => size */
    private array $allocMap = [];

    public function __construct(int $capacity)
    {
        if ($capacity <= 0) {
            throw new MemoryException('Capacity must be positive');
        }
        $this->capacity = $capacity;
        $this->memory = array_fill(0, $capacity, 0);
        $this->freeList[] = ['offset' => 0, 'size' => $capacity];
    }

    /**
     * Allocate a block of $size bytes.
     *
     * @return int offset of allocated block
     * @throws MemoryException on insufficient space
     */
    public function allocate(int $size): int
    {
        if ($size <= 0) {
            throw new MemoryException('Allocation size must be positive');
        }
        foreach ($this->freeList as $index => $block) {
            if ($block['size'] >= $size) {
                $offset = $block['offset'];
                // allocate from the beginning of the free block
                $remaining = $block['size'] - $size;
                if ($remaining > 0) {
                    $this->freeList[$index] = [
                        'offset' => $offset + $size,
                        'size'   => $remaining,
                    ];
                } else {
                    // exact fit, remove free block
                    array_splice($this->freeList, $index, 1);
                }
                $this->allocMap[$offset] = $size;
                return $offset;
            }
        }
        throw new MemoryException('Out of memory: cannot allocate '.$size.' bytes');
    }

    /**
     * Free a previously allocated block.
     *
     * @throws MemoryException on invalid offset or double free
     */
    public function free(int $offset): void
    {
        if (!isset($this->allocMap[$offset])) {
            throw new MemoryException('Invalid free: no allocation at offset '.$offset);
        }
        $size = $this->allocMap[$offset];
        unset($this->allocMap[$offset]);
        $this->freeList[] = ['offset' => $offset, 'size' => $size];
        $this->mergeFreeList();
    }

    /**
     * Write binary data into allocated memory.
     *
     * @throws MemoryException on out-of-bounds write
     */
    public function write(int $offset, string $data): void
    {
        $size = strlen($data);
        $this->ensureWritable($offset, $size);
        for ($i = 0; $i < $size; $i++) {
            $this->memory[$offset + $i] = ord($data[$i]);
        }
    }

    /**
     * Read $length bytes from memory starting at $offset.
     *
     * @throws MemoryException on out-of-bounds read
     */
    public function read(int $offset, int $length): string
    {
        $this->ensureReadable($offset, $length);
        $bytes = '';
        for ($i = 0; $i < $length; $i++) {
            $bytes .= chr($this->memory[$offset + $i]);
        }
        return $bytes;
    }

    /** @return int current free bytes */
    public function freeBytes(): int
    {
        $total = 0;
        foreach ($this->freeList as $block) {
            $total += $block['size'];
        }
        return $total;
    }

    /** @return int current allocated bytes */
    public function allocatedBytes(): int
    {
        $total = 0;
        foreach ($this->allocMap as $size) {
            $total += $size;
        }
        return $total;
    }

    private function ensureWritable(int $offset, int $size): void
    {
        if (!isset($this->allocMap[$offset])) {
            throw new MemoryException('Write error: offset not allocated');
        }
        $allocSize = $this->allocMap[$offset];
        if ($size > $allocSize) {
            throw new MemoryException('Write error: exceeds allocated block size');
        }
        if ($offset + $size > $this->capacity) {
            throw new MemoryException('Write error: out of arena bounds');
        }
    }

    private function ensureReadable(int $offset, int $size): void
    {
        if (!isset($this->allocMap[$offset])) {
            throw new MemoryException('Read error: offset not allocated');
        }
        $allocSize = $this->allocMap[$offset];
        if ($size > $allocSize) {
            throw new MemoryException('Read error: exceeds allocated block size');
        }
        if ($offset + $size > $this->capacity) {
            throw new MemoryException('Read error: out of arena bounds');
        }
    }

    /** Merge adjacent free blocks to avoid fragmentation */
    private function mergeFreeList(): void
    {
        // sort by offset
        usort($this->freeList, static fn($a, $b) => $a['offset'] <=> $b['offset']);
        $merged = [];
        foreach ($this->freeList as $block) {
            if (empty($merged)) {
                $merged[] = $block;
                continue;
            }
            $lastIdx = count($merged) - 1;
            $last = $merged[$lastIdx];
            if ($last['offset'] + $last['size'] === $block['offset']) {
                // adjacent, merge
                $merged[$lastIdx]['size'] += $block['size'];
            } else {
                $merged[] = $block;
            }
        }
        $this->freeList = $merged;
    }
}
