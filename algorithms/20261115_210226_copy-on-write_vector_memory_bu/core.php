<?php
declare(strict_types=1);

namespace CowVector;

use OutOfBoundsException;
use UnderflowException;

/**
 * Internal buffer that holds the actual data and a manual reference count.
 */
final class Buffer
{
    /** @var array<int,mixed> */
    private array $data;
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

    /**
     * @param array<int,mixed> $data
     */
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
    }

    public function getRefCount(): int
    {
        return $this->refCount;
    }
}

/**
 * Copy‑On‑Write vector implementation.
 */
final class CowVector
{
    private Buffer $buffer;

    /**
     * @param array<int,mixed> $initial
     */
    public function __construct(array $initial = [])
    {
        $this->buffer = new Buffer($initial);
    }

    /**
     * Clone shares the underlying buffer and bumps its reference count.
     */
    public function __clone(): void
    {
        $this->buffer->incRef();
    }

    /**
     * Destructor decrements the reference count.
     */
    public function __destruct()
    {
        $this->buffer->decRef();
    }

    /** @return int */
    public function count(): int
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

    /** @param mixed $value */
    public function push($value): void
    {
        $this->ensureUnique();
        $data = $this->buffer->getData();
        $data[] = $value;
        $this->buffer->setData($data);
    }

    /** @return mixed */
    public function pop()
    {
        $this->ensureUnique();
        $data = $this->buffer->getData();
        if (empty($data)) {
            throw new UnderflowException('Cannot pop from empty vector');
        }
        $value = array_pop($data);
        $this->buffer->setData($data);
        return $value;
    }

    /** @return array<int,mixed> */
    public function toArray(): array
    {
        return $this->buffer->getData();
    }

    /**
     * Ensure this instance has exclusive ownership of the buffer.
     * If the buffer is shared, copy its data into a new buffer.
     */
    private function ensureUnique(): void
    {
        if ($this->buffer->getRefCount() > 1) {
            $oldBuffer = $this->buffer;
            $oldBuffer->decRef();
            $this->buffer = new Buffer($oldBuffer->getData());
        }
    }
}
