<?php
declare(strict_types=1);

/**
 * Internal buffer used by CowVector.
 * Manages reference counting to enable copy‑on‑write semantics.
 */
final class CowBuffer
{
    /** @var array<int,mixed> */
    private array $data;
    /** @var int */
    private int $refCount = 1;

    /**
     * @param array<int,mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /** @return array<int,mixed> */
    public function getData(): array
    {
        return $this->data;
    }

    /** @param array<int,mixed> $data */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function incRef(): void
    {
        $this->refCount++;
    }

    public function decRef(): void
    {
        $this->refCount--;
        // When refCount reaches zero we let PHP GC free the object.
    }

    public function getRefCount(): int
    {
        return $this->refCount;
    }
}

/**
 * Copy‑On‑Write Vector.
 *
 * Provides O(1) amortized push/pop and O(1) random access.
 * Copies underlying storage only when a write occurs on a shared buffer.
 */
final class CowVector
{
    /** @var CowBuffer */
    private CowBuffer $buffer;

    /**
     * @param array<int,mixed> $initial
     */
    public function __construct(array $initial = [])
    {
        $this->buffer = new CowBuffer(array_values($initial));
    }

    /**
     * Create a new vector that shares the same underlying buffer.
     * The buffer reference count is increased.
     */
    public function copy(): CowVector
    {
        $clone = new self();
        $clone->buffer = $this->buffer;
        $this->buffer->incRef();
        return $clone;
    }

    /** @return int */
    public function length(): int
    {
        return count($this->buffer->getData());
    }

    /**
     * @param int $index
     * @return mixed
     * @throws OutOfBoundsException
     */
    public function get(int $index)
    {
        $data = $this->buffer->getData();
        if (!array_key_exists($index, $data)) {
            throw new OutOfBoundsException("Index $index out of bounds");
        }
        return $data[$index];
    }

    /**
     * @param int   $index
     * @param mixed $value
     * @throws OutOfBoundsException
     */
    public function set(int $index, $value): void
    {
        $this->ensureUnique();
        $data = $this->buffer->getData();
        if (!array_key_exists($index, $data)) {
            throw new OutOfBoundsException("Index $index out of bounds");
        }
        $data[$index] = $value;
        $this->buffer->setData($data);
    }

    /**
     * Append a value to the end of the vector.
     *
     * @param mixed $value
     */
    public function push($value): void
    {
        $this->ensureUnique();
        $data = $this->buffer->getData();
        $data[] = $value;
        $this->buffer->setData($data);
    }

    /**
     * Remove and return the last element.
     *
     * @return mixed
     * @throws UnderflowException
     */
    public function pop()
    {
        $this->ensureUnique();
        $data = $this->buffer->getData();
        if (empty($data)) {
            throw new UnderflowException("Cannot pop from empty vector");
        }
        $value = array_pop($data);
        $this->buffer->setData($data);
        return $value;
    }

    /**
     * @return array<int,mixed>
     */
    public function toArray(): array
    {
        return $this->buffer->getData();
    }

    /**
     * Ensure the current vector has exclusive ownership of its buffer.
     * If the buffer is shared (refCount > 1), clone the data into a new buffer.
     */
    private function ensureUnique(): void
    {
        if ($this->buffer->getRefCount() > 1) {
            // Detach: decrement old buffer ref count and create a fresh copy.
            $oldBuffer = $this->buffer;
            $oldBuffer->decRef();
            $this->buffer = new CowBuffer($oldBuffer->getData());
        }
        // If refCount == 1 we already have exclusive ownership.
    }

    public function __destruct()
    {
        $this->buffer->decRef();
    }
}
