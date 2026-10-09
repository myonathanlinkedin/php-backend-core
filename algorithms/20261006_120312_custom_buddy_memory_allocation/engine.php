<?php

namespace BuddyAllocator;

use BuddyAllocator\AllocatorInterface;

class BuddyAllocator implements AllocatorInterface
{
    private int $poolSize;
    private int $maxExp;
    private int $minExp;
    private array $freeLists; // array of arrays of offsets
    private array $allocations; // offset => sizeExp

    public function __construct(int $poolSize, int $minExp = 0)
    {
        if ($poolSize <= 0 || ($poolSize & ($poolSize - 1)) !== 0) {
            throw new \InvalidArgumentException('Pool size must be a positive power of two.');
        }
        $this->poolSize = $poolSize;
        $this->maxExp = (int)log($poolSize, 2);
        $this->minExp = $minExp;
        $this->freeLists = [];
        for ($i = $this->minExp; $i <= $this->maxExp; $i++) {
            $this->freeLists[$i] = [];
        }
        $this->freeLists[$this->maxExp][] = 0; // whole pool free
        $this->allocations = [];
    }

    public function getPoolSize(): int
    {
        return $this->poolSize;
    }

    public function allocate(int $size): ?int
    {
        if ($size <= 0 || $size > $this->poolSize) {
            return null;
        }
        $exp = $this->sizeToExp($size);
        // Find suitable block
        $foundExp = null;
        for ($i = $exp; $i <= $this->maxExp; $i++) {
            if (!empty($this->freeLists[$i])) {
                $foundExp = $i;
                break;
            }
        }
        if ($foundExp === null) {
            return null; // no block available
        }
        $offset = array_pop($this->freeLists[$foundExp]); // remove last
        // Split if necessary
        while ($foundExp > $exp) {
            $foundExp--;
            $buddyOffset = $offset + (1 << $foundExp);
            $this->freeLists[$foundExp][] = $buddyOffset;
        }
        $this->allocations[$offset] = $exp;
        return $offset;
    }

    public function free(int $offset): void
    {
        if (!isset($this->allocations[$offset])) {
            // invalid free, ignore
            return;
        }
        $exp = $this->allocations[$offset];
        unset($this->allocations[$offset]);

        while ($exp < $this->maxExp) {
            $buddy = $offset ^ (1 << $exp);
            $index = array_search($buddy, $this->freeLists[$exp], true);
            if ($index === false) {
                break; // buddy not free
            }
            // remove buddy
            array_splice($this->freeLists[$exp], $index, 1);
            $offset = min($offset, $buddy);
            $exp++;
        }
        $this->freeLists[$exp][] = $offset;
    }

    private function sizeToExp(int $size): int
    {
        $exp = 0;
        $blockSize = 1;
        while ($blockSize < $size) {
            $blockSize <<= 1;
            $exp++;
        }
        return $exp;
    }
}
