<?php
declare(strict_types=1);

interface KeyValueStore {
    public function put(string $key, string $value): void;
    public function delete(string $key): void;
    public function get(string $key): ?string;
}

final class Entry {
    public string $key;
    public ?string $value; // null indicates tombstone (deletion)
    public int $seq;       // monotonically increasing sequence number

    public function __construct(string $key, ?string $value, int $seq) {
        $this->key   = $key;
        $this->value = $value;
        $this->seq   = $seq;
    }
}
