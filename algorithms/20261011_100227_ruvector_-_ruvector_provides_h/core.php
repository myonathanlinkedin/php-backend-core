<?php
declare(strict_types=1);

/**
 * RuVector – a high‑performance dynamic array.
 *
 * Provides O(1) amortized push/pop, O(1) random access, and deterministic
 * memory layout using SplFixedArray internally.
 *
 * @template T
 */
final class RuVector implements Countable, IteratorAggregate
{
    /** @var SplFixedArray<T> */
    private SplFixedArray $buffer;

    /** @var int */
    private int $size = 0;

    /** @var int */
    private int $capacity;

    /**
     * @param int $initialCapacity Minimum capacity, must be >= 1.
     */
    public function __construct(int $initialCapacity = 16)
    {
        if ($initialCapacity < 1) {
            throw new InvalidArgumentException('Initial capacity must be >= 1');
        }
        $this->capacity = $initialCapacity;
        $this->buffer   = new SplFixedArray($this->capacity);
    }

    /**
     * Append a value to the end of the vector.
     *
     * @template V
     * @param V $value
     * @return void
     */
    public function push(mixed $value): void
    {
        if ($this->size === $this->capacity) {
            $this->grow();
        }
        $this->buffer[$this->size] = $value;
        $this->size++;
    }

    /**
     * Remove and return the last element.
     *
     * @return mixed
     * @throws UnderflowException if the vector is empty.
     */
    public function pop(): mixed
    {
        if ($this->size === 0) {
            throw new UnderflowException('Cannot pop from an empty RuVector');
        }
        $this->size--;
        $value = $this->buffer[$this->size];
        $this->buffer[$this->size] = null; // free reference
        return $value;
    }

    /**
     * Retrieve element at given index.
     *
     * @param int $index Zero‑based index.
     * @return mixed
     * @throws OutOfRangeException if index is invalid.
     */
    public function get(int $index): mixed
    {
        $this->assertValidIndex($index);
        return $this->buffer[$index];
    }

    /**
     * Overwrite element at given index.
     *
     * @param int $index Zero‑based index.
     * @param mixed $value New value.
     * @return void
     * @throws OutOfRangeException if index is invalid.
     */
    public function set(int $index, mixed $value): void
    {
        $this->assertValidIndex($index);
        $this->buffer[$index] = $value;
    }

    /**
     * Current number of stored elements.
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * Current allocated capacity.
     */
    public function capacity(): int
    {
        return $this->capacity;
    }

    /**
     * Remove all elements, keeping allocated capacity unchanged.
     */
    public function clear(): void
    {
        for ($i = 0; $i < $this->size; $i++) {
            $this->buffer[$i] = null;
        }
        $this->size = 0;
    }

    /**
     * Return a native PHP array copy of the stored elements.
     *
     * @return array<int, mixed>
     */
    public function toArray(): array
    {
        $result = [];
        for ($i = 0; $i < $this->size; $i++) {
            $result[$i] = $this->buffer[$i];
        }
        return $result;
    }

    /**
     * Countable interface – alias for size().
     */
    public function count(): int
    {
        return $this->size;
    }

    /**
     * IteratorAggregate – enables foreach iteration.
     *
     * @return Traversable<int, mixed>
     */
    public function getIterator(): Traversable
    {
        for ($i = 0; $i < $this->size; $i++) {
            yield $i => $this->buffer[$i];
        }
    }

    /**
     * Internal method to double the capacity.
     */
    private function grow(): void
    {
        $newCapacity = $this->capacity * 2;
        $newBuffer   = new SplFixedArray($newCapacity);
        for ($i = 0; $i < $this->size; $i++) {
            $newBuffer[$i] = $this->buffer[$i];
        }
        $this->buffer   = $newBuffer;
        $this->capacity = $newCapacity;
    }

    /**
     * Validate that $index is within [0, size).
     *
     * @throws OutOfRangeException
     */
    private function assertValidIndex(int $index): void
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfRangeException("Index {$index} out of range (size {$this->size})");
        }
    }
}
