<?php
declare(strict_types=1);

/**
 * High‑performance dynamic array for real‑time decisions.
 * Provides O(1) amortized push/pop and O(1) random access.
 */
final class RuVector
{
    /** @var array<int, mixed> Internal storage */
    private array $data = [];

    /** @var int Current number of elements */
    private int $size = 0;

    /** @var int Allocated capacity */
    private int $capacity;

    /**
     * @param int $initialCapacity Minimum capacity, must be >= 1
     */
    public function __construct(int $initialCapacity = 16)
    {
        if ($initialCapacity < 1) {
            throw new InvalidArgumentException('Initial capacity must be >= 1');
        }
        $this->capacity = $initialCapacity;
        // Pre‑allocate with nulls to avoid PHP array rehashing overhead
        $this->data = array_fill(0, $this->capacity, null);
    }

    /** @return int */
    public function size(): int
    {
        return $this->size;
    }

    /** @return int */
    public function capacity(): int
    {
        return $this->capacity;
    }

    /** @param mixed $value */
    public function push($value): void
    {
        if ($this->size === $this->capacity) {
            $this->grow();
        }
        $this->data[$this->size] = $value;
        $this->size++;
    }

    /** @return mixed */
    public function pop()
    {
        if ($this->size === 0) {
            throw new UnderflowException('Cannot pop from empty RuVector');
        }
        $this->size--;
        $value = $this->data[$this->size];
        $this->data[$this->size] = null; // free reference
        return $value;
    }

    /**
     * @param int $index Zero‑based index
     * @return mixed
     */
    public function get(int $index)
    {
        $this->assertIndex($index);
        return $this->data[$index];
    }

    /**
     * @param int $index Zero‑based index
     * @param mixed $value
     */
    public function set(int $index, $value): void
    {
        $this->assertIndex($index);
        $this->data[$index] = $value;
    }

    /** @return void */
    public function clear(): void
    {
        $this->data = array_fill(0, $this->capacity, null);
        $this->size = 0;
    }

    /** @return array<int, mixed> */
    public function toArray(): array
    {
        return array_slice($this->data, 0, $this->size);
    }

    /**
     * @param callable(mixed):mixed $fn
     * @return RuVector
     */
    public function map(callable $fn): RuVector
    {
        $result = new self($this->capacity);
        for ($i = 0; $i < $this->size; $i++) {
            $result->push($fn($this->data[$i], $i));
        }
        return $result;
    }

    /**
     * @param callable(mixed):bool $fn
     * @return RuVector
     */
    public function filter(callable $fn): RuVector
    {
        $result = new self($this->capacity);
        for ($i = 0; $i < $this->size; $i++) {
            $value = $this->data[$i];
            if ($fn($value, $i)) {
                $result->push($value);
            }
        }
        return $result;
    }

    /**
     * @param callable(mixed,mixed):mixed $fn
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(callable $fn, $initial = null)
    {
        $acc = $initial;
        for ($i = 0; $i < $this->size; $i++) {
            $acc = $fn($acc, $this->data[$i], $i);
        }
        return $acc;
    }

    /**
     * Real‑time decision: true if any element satisfies $predicate.
     *
     * @param callable(mixed):bool $predicate
     * @return bool
     */
    public function decide(callable $predicate): bool
    {
        for ($i = 0; $i < $this->size; $i++) {
            if ($predicate($this->data[$i], $i)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Snapshot of internal state for debugging / agent memory.
     *
     * @return array<string,mixed>
     */
    public function memorySnapshot(): array
    {
        return [
            'size' => $this->size,
            'capacity' => $this->capacity,
            'data' => $this->toArray(),
        ];
    }

    /** @return void */
    private function grow(): void
    {
        $newCapacity = $this->capacity * 2;
        $newData = array_fill(0, $newCapacity, null);
        for ($i = 0; $i < $this->size; $i++) {
            $newData[$i] = $this->data[$i];
        }
        $this->data = $newData;
        $this->capacity = $newCapacity;
    }

    /** @param int $index */
    private function assertIndex(int $index): void
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException("Index $index out of bounds");
        }
    }
}
