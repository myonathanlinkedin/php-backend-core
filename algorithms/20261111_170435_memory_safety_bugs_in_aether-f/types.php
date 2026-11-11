<?php
declare(strict_types=1);

namespace AetherFlux;

/**
 * Exception thrown when a memory safety rule is violated.
 */
final class MemorySafetyException extends \Exception
{
}

/**
 * Represents a contiguous block of allocated memory.
 */
final class MemoryBlock
{
    /** @var int Starting address (offset) of the block in the simulated heap */
    public int $ptr;

    /** @var int Size of the block in bytes */
    public int $size;

    /** @var bool Whether the block has been freed */
    public bool $freed = false;

    /**
     * @param int $ptr
     * @param int $size
     */
    public function __construct(int $ptr, int $size)
    {
        $this->ptr = $ptr;
        $this->size = $size;
    }
}

/**
 * Interface for allocating and freeing memory blocks.
 */
interface MemoryAllocator
{
    /**
     * Allocate a block of memory.
     *
     * @param int $size Number of bytes to allocate (must be > 0)
     * @return int Pointer (offset) to the start of the allocated block
     * @throws MemorySafetyException on invalid size or out‑of‑memory
     */
    public function allocate(int $size): int;

    /**
     * Free a previously allocated block.
     *
     * @param int $ptr Pointer returned by allocate()
     * @throws MemorySafetyException on double free or invalid pointer
     */
    public function free(int $ptr): void;
}

/**
 * Interface for reading from and writing to allocated memory.
 */
interface MemoryAccessor
{
    /**
     * Read a byte from a memory block.
     *
     * @param int $ptr   Pointer to the start of the block
     * @param int $offset Zero‑based offset inside the block
     * @return int Byte value (0‑255)
     * @throws MemorySafetyException on out‑of‑bounds or use‑after‑free
     */
    public function read(int $ptr, int $offset): int;

    /**
     * Write a byte into a memory block.
     *
     * @param int $ptr   Pointer to the start of the block
     * @param int $offset Zero‑based offset inside the block
     * @param int $value Byte value (0‑255)
     * @throws MemorySafetyException on out‑of‑bounds, use‑after‑free or invalid value
     */
    public function write(int $ptr, int $offset, int $value): void;
}
