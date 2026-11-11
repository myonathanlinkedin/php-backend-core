<?php
declare(strict_types=1);

namespace AetherFlux;

require_once __DIR__ . '/types.php';

/**
 * Simple deterministic memory manager that simulates a linear heap.
 *
 * Guarantees:
 * - O(1) allocation and deallocation (bump pointer strategy)
 * - Strict bounds checking on every read/write
 * - Detection of double free, use‑after‑free, out‑of‑bounds, and invalid sizes
 */
final class SimpleMemoryManager implements MemoryAllocator, MemoryAccessor
{
    /** @var int Maximum heap size in bytes (1 MiB) */
    private const MAX_HEAP = 1024 * 1024;

    /** @var array<int, int> Simulated heap; each entry holds a byte (0‑255) */
    private array $heap = [];

    /** @var array<int, MemoryBlock> Map of active blocks keyed by pointer */
    private array $blocks = [];

    /** @var int Next free address in the heap */
    private int $nextPtr = 0;

    public function __construct()
    {
        // Pre‑allocate the heap with zeroes for deterministic behaviour.
        $this->heap = array_fill(0, self::MAX_HEAP, 0);
    }

    public function allocate(int $size): int
    {
        if ($size <= 0) {
            throw new MemorySafetyException('Allocation size must be positive.');
        }

        if ($this->nextPtr + $size > self::MAX_HEAP) {
            throw new MemorySafetyException('Out of memory: cannot allocate requested size.');
        }

        $ptr = $this->nextPtr;
        $this->nextPtr += $size;

        $block = new MemoryBlock($ptr, $size);
        $this->blocks[$ptr] = $block;

        return $ptr;
    }

    public function free(int $ptr): void
    {
        $block = $this->blocks[$ptr] ?? null;
        if ($block === null) {
            throw new MemorySafetyException('Free error: pointer does not reference an allocated block.');
        }
        if ($block->freed) {
            throw new MemorySafetyException('Double free detected.');
        }
        $block->freed = true;
        // Note: we do NOT reclaim space (bump‑pointer allocator) – this keeps the model simple and deterministic.
    }

    public function read(int $ptr, int $offset): int
    {
        $block = $this->blocks[$ptr] ?? null;
        if ($block === null) {
            throw new MemorySafetyException('Read error: invalid pointer.');
        }
        if ($block->freed) {
            throw new MemorySafetyException('Read after free detected.');
        }
        if ($offset < 0 || $offset >= $block->size) {
            throw new MemorySafetyException('Read out of bounds.');
        }

        $addr = $ptr + $offset;
        return $this->heap[$addr];
    }

    public function write(int $ptr, int $offset, int $value): void
    {
        if ($value < 0 || $value > 255) {
            throw new MemorySafetyException('Write error: value must be a byte (0‑255).');
        }

        $block = $this->blocks[$ptr] ?? null;
        if ($block === null) {
            throw new MemorySafetyException('Write error: invalid pointer.');
        }
        if ($block->freed) {
            throw new MemorySafetyException('Write after free detected.');
        }
        if ($offset < 0 || $offset >= $block->size) {
            throw new MemorySafetyException('Write out of bounds.');
        }

        $addr = $ptr + $offset;
        $this->heap[$addr] = $value;
    }

    /**
     * Helper for unit tests: expose internal state in a read‑only fashion.
     *
     * @return array<int, MemoryBlock>
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }
}
