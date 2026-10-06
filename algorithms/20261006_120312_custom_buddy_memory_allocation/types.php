<?php

namespace BuddyAllocator;

interface AllocatorInterface
{
    public function allocate(int $size): ?int;
    public function free(int $offset): void;
    public function getPoolSize(): int;
}

class MemoryBlock
{
    public int $offset;
    public int $sizeExp; // exponent of size (size = 1 << sizeExp)

    public function __construct(int $offset, int $sizeExp)
    {
        $this->offset = $offset;
        $this->sizeExp = $sizeExp;
    }
}
