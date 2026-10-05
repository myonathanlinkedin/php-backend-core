<?php
declare(strict_types=1);

namespace HeapVsStack;

/**
 * Represents a memory segment (Stack or Heap) with allocation tracking.
 */
final class MemorySegment
{
    private int $capacity;
    private int $used;
    private array $allocations = [];
    private string $type;

    public function __construct(string $type, int $capacity)
    {
        $this->type = $type;
        $this->capacity = $capacity;
        $this->used = 0;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getUsed(): int
    {
        return $this->used;
    }

    public function getFree(): int
    {
        return $this->capacity - $this->used;
    }

    public function isFull(): bool
    {
        return $this->used >= $this->capacity;
    }

    /**
     * Allocate memory. Returns unique ID or null if full.
     */
    public function allocate(int $size): ?int
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException("Size must be positive");
        }
        if ($this->used + $size > $this->capacity) {
            return null;
        }
        $id = count($this->allocations);
        $this->allocations[$id] = $size;
        $this->used += $size;
        return $id;
    }

    /**
     * Free memory by ID.
     */
    public function free(int $id): bool
    {
        if (!isset($this->allocations[$id])) {
            return false;
        }
        $this->used -= $this->allocations[$id];
        unset($this->allocations[$id]);
        return true;
    }

    public function getAllocationCount(): int
    {
        return count($this->allocations);
    }
}

/**
 * Simulates C memory management: Stack (LIFO, fixed) vs Heap (dynamic, malloc/free).
 */
final class MemoryManager
{
    private MemorySegment $stack;
    private MemorySegment $heap;
    private int $stackPointer;
    private array $stackFrames = [];

    public function __construct(int $stackCapacity = 1024, int $heapCapacity = 4096)
    {
        $this->stack = new MemorySegment('stack', $stackCapacity);
        $this->heap = new MemorySegment('heap', $heapCapacity);
        $this->stackPointer = 0;
    }

    public function getStack(): MemorySegment
    {
        return $this->stack;
    }

    public function getHeap(): MemorySegment
    {
        return $this->heap;
    }

    /**
     * Simulate function call: push frame onto stack.
     */
    public function pushFrame(int $localVarsSize): bool
    {
        $id = $this->stack->allocate($localVarsSize);
        if ($id === null) {
            return false; // Stack overflow
        }
        $this->stackFrames[] = ['id' => $id, 'size' => $localVarsSize];
        $this->stackPointer++;
        return true;
    }

    /**
     * Simulate function return: pop frame from stack.
     */
    public function popFrame(): bool
    {
        if (empty($this->stackFrames)) {
            return false;
        }
        $frame = array_pop($this->stackFrames);
        $this->stack->free($frame['id']);
        $this->stackPointer--;
        return true;
    }

    /**
     * Simulate malloc: allocate on heap.
     */
    public function malloc(int $size): ?int
    {
        return $this->heap->allocate($size);
    }

    /**
     * Simulate free: deallocate from heap.
     */
    public function free(int $id): bool
    {
        return $this->heap->free($id);
    }

    /**
     * Get current stack depth.
     */
    public function getStackDepth(): int
    {
        return $this->stackPointer;
    }

    /**
     * Check for stack overflow condition.
     */
    public function isStackOverflow(): bool
    {
        return $this->stack->isFull();
    }

    /**
     * Check for heap exhaustion.
     */
    public function isHeapExhausted(): bool
    {
        return $this->heap->isFull();
    }

    /**
     * Get memory usage statistics.
     */
    public function getStats(): array
    {
        return [
            'stack_used' => $this->stack->getUsed(),
            'stack_free' => $this->stack->getFree(),
            'stack_depth' => $this->stackPointer,
            'heap_used' => $this->heap->getUsed(),
            'heap_free' => $this->heap->getFree(),
            'heap_allocations' => $this->heap->getAllocationCount(),
        ];
    }
}
