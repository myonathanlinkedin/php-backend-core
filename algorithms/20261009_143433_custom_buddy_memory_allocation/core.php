<?php

class BuddyAllocator
{
    private int $totalSize;
    private int $minBlockSize;
    /** @var array<int, array<int>> */
    private array $freeLists = [];
    /** @var array<int, int> */
    private array $allocations = [];

    public function __construct(int $totalSize, int $minBlockSize)
    {
        if ($totalSize <= 0 || $minBlockSize <= 0) {
            throw new InvalidArgumentException('Sizes must be positive.');
        }
        if (($totalSize & ($totalSize - 1)) !== 0) {
            throw new InvalidArgumentException('Total size must be a power of two.');
        }
        if (($minBlockSize & ($minBlockSize - 1)) !== 0) {
            throw new InvalidArgumentException('Minimum block size must be a power of two.');
        }
        if ($minBlockSize > $totalSize) {
            throw new InvalidArgumentException('Minimum block size cannot exceed total size.');
        }
        $this->totalSize = $totalSize;
        $this->minBlockSize = $minBlockSize;
        $this->freeLists[$totalSize] = [0];
    }

    public function allocate(int $size): ?int
    {
        if ($size <= 0) {
            return null;
        }
        $blockSize = $this->nextPowerOfTwo(max($size, $this->minBlockSize));
        if ($blockSize > $this->totalSize) {
            return null;
        }
        $offset = $this->findAndSplit($blockSize);
        if ($offset === null) {
            return null;
        }
        $this->allocations[$offset] = $blockSize;
        return $offset;
    }

    public function free(int $offset): void
    {
        if (!isset($this->allocations[$offset])) {
            throw new InvalidArgumentException('Invalid offset: not allocated.');
        }
        $size = $this->allocations[$offset];
        unset($this->allocations[$offset]);
        $this->merge($offset, $size);
    }

    public function getAllocatedSize(int $offset): int
    {
        return $this->allocations[$offset] ?? 0;
    }

    private function findAndSplit(int $size): ?int
    {
        $currentSize = $size;
        while ($currentSize <= $this->totalSize) {
            if (!empty($this->freeLists[$currentSize])) {
                $offset = array_pop($this->freeLists[$currentSize]);
                while ($currentSize > $size) {
                    $currentSize >>= 1;
                    $buddy = $offset + $currentSize;
                    $this->freeLists[$currentSize][] = $buddy;
                }
                return $offset;
            }
            $currentSize <<= 1;
        }
        return null;
    }

    private function merge(int $offset, int $size): void
    {
        $currentOffset = $offset;
        $currentSize = $size;
        while ($currentSize < $this->totalSize) {
            $buddyOffset = $currentOffset ^ $currentSize;
            $index = array_search($buddyOffset, $this->freeLists[$currentSize] ?? [], true);
            if ($index === false) {
                break;
            }
            array_splice($this->freeLists[$currentSize], $index, 1);
            $currentOffset = min($currentOffset, $buddyOffset);
            $currentSize <<= 1;
        }
        $this->freeLists[$currentSize][] = $currentOffset;
    }

    private function nextPowerOfTwo(int $n): int
    {
        if ($n <= 1) {
            return 1;
        }
        $n--;
        $n |= $n >> 1;
        $n |= $n >> 2;
        $n |= $n >> 4;
        $n |= $n >> 8;
        $n |= $n >> 16;
        $n++;
        return $n;
    }
}
