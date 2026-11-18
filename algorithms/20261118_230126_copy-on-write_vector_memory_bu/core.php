<?php
declare(strict_types=1);

class CowBuffer
{
    /** @var array<int,mixed> */
    public array $data;
    public int $capacity;
    private int $refCount = 1;

    public function __construct(int $capacity)
    {
        if ($capacity < 1) {
            $capacity = 1;
        }
        $this->capacity = $capacity;
        $this->data = array_fill(0, $capacity, null);
    }

    public function incRef(): void
    {
        $this->refCount++;
    }

    public function decRef(): void
    {
        $this->refCount--;
        if ($this->refCount < 0) {
            $this->refCount = 0;
        }
    }

    public function getRefCount(): int
    {
        return $this->refCount;
    }

    public function isShared(): bool
    {
        return $this->refCount > 1;
    }

    public function copy(): CowBuffer
    {
        $new = new CowBuffer($this->capacity);
        $new->data = $this->data;
        return $new;
    }
}

class CowVector
{
    private CowBuffer $buffer;
    private int $size = 0;

    public function __construct(int $initialCapacity = 4)
    {
        $this->buffer = new CowBuffer($initialCapacity);
    }

    public function __clone(): void
    {
        $this->buffer->incRef();
    }

    public function __destruct()
    {
        $this->buffer->decRef();
    }

    private function ensureUnique(): void
    {
        if ($this->buffer->isShared()) {
            $old = $this->buffer;
            $this->buffer = $old->copy();
            $old->decRef();
        }
    }

    private function resize(int $newCapacity): void
    {
        $newData = array_fill(0, $newCapacity, null);
        for ($i = 0; $i < $this->size; $i++) {
            $newData[$i] = $this->buffer->data[$i];
        }
        $this->buffer->data = $newData;
        $this->buffer->capacity = $newCapacity;
    }

    public function push(mixed $value): void
    {
        $this->ensureUnique();
        if ($this->size === $this->buffer->capacity) {
            $this->resize($this->buffer->capacity * 2);
        }
        $this->buffer->data[$this->size] = $value;
        $this->size++;
    }

    public function pop(): mixed
    {
        if ($this->size === 0) {
            throw new UnderflowException('Pop from empty vector');
        }
        $this->ensureUnique();
        $this->size--;
        $value = $this->buffer->data[$this->size];
        $this->buffer->data[$this->size] = null;
        return $value;
    }

    public function get(int $index): mixed
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException('Index out of bounds');
        }
        return $this->buffer->data[$index];
    }

    public function set(int $index, mixed $value): void
    {
        if ($index < 0 || $index >= $this->size) {
            throw new OutOfBoundsException('Index out of bounds');
        }
        $this->ensureUnique();
        $this->buffer->data[$index] = $value;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function capacity(): int
    {
        return $this->buffer->capacity;
    }

    public function getRefCount(): int
    {
        return $this->buffer->getRefCount();
    }
}
