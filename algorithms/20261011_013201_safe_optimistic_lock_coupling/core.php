<?php

class Node {
    public int $key;
    public mixed $value;
    public ?Node $next;
    public int $version;
    public bool $locked;

    public function __construct(int $key, mixed $value, ?Node $next = null) {
        $this->key = $key;
        $this->value = $value;
        $this->next = $next;
        $this->version = 0;
        $this->locked = false;
    }

    public function lock(): void {
        $this->locked = true;
    }

    public function unlock(): void {
        $this->locked = false;
    }

    public function incrementVersion(): void {
        $this->version++;
    }
}

class SafeOptimisticLockCouplingList {
    private Node $head;

    public function __construct() {
        $this->head = new Node(-PHP_INT_MAX, null);
    }

    private function readVersion(Node $node): int {
        return $node->version;
    }

    private function lockNode(Node $node): void {
        $node->lock();
    }

    private function unlockNode(Node $node): void {
        $node->unlock();
    }

    public function find(int $key): ?Node {
        $prev = $this->head;
        $curr = $prev->next;
        while ($curr !== null && $curr->key < $key) {
            $prev = $curr;
            $curr = $curr->next;
        }
        if ($curr !== null && $curr->key === $key) {
            return $curr;
        }
        return null;
    }

    public function insert(int $key, mixed $value): bool {
        $maxRetries = 3;
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $prev = $this->head;
            $prevVer = $this->readVersion($prev);
            $curr = $prev->next;
            $currVer = $curr !== null ? $this->readVersion($curr) : -1;

            while ($curr !== null && $curr->key < $key) {
                $prev = $curr;
                $prevVer = $currVer;
                $curr = $curr->next;
                $currVer = $curr !== null ? $this->readVersion($curr) : -1;
            }

            if ($curr !== null && $curr->key === $key) {
                return false;
            }

            $this->lockNode($prev);
            if ($curr !== null) {
                $this->lockNode($curr);
            }

            if ($prev->version !== $prevVer || ($curr !== null && $curr->version !== $currVer)) {
                $this->unlockNode($prev);
                if ($curr !== null) {
                    $this->unlockNode($curr);
                }
                continue;
            }

            $newNode = new Node($key, $value, $curr);
            $prev->next = $newNode;
            $prev->incrementVersion();
            if ($curr !== null) {
                $curr->incrementVersion();
            }

            $this->unlockNode($prev);
            if ($curr !== null) {
                $this->unlockNode($curr);
            }

            return true;
        }
        return false;
    }

    public function delete(int $key): bool {
        $maxRetries = 3;
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $prev = $this->head;
            $prevVer = $this->readVersion($prev);
            $curr = $prev->next;
            $currVer = $curr !== null ? $this->readVersion($curr) : -1;

            while ($curr !== null && $curr->key < $key) {
                $prev = $curr;
                $prevVer = $currVer;
                $curr = $curr->next;
                $currVer = $curr !== null ? $this->readVersion($curr) : -1;
            }

            if ($curr === null || $curr->key !== $key) {
                return false;
            }

            $this->lockNode($prev);
            $this->lockNode($curr);

            if ($prev->version !== $prevVer || $curr->version !== $currVer) {
                $this->unlockNode($prev);
                $this->unlockNode($curr);
                continue;
            }

            $prev->next = $curr->next;
            $prev->incrementVersion();
            $curr->incrementVersion();

            $this->unlockNode($prev);
            $this->unlockNode($curr);

            return true;
        }
        return false;
    }

    public function toArray(): array {
        $arr = [];
        $node = $this->head->next;
        while ($node !== null) {
            $arr[] = ['key' => $node->key, 'value' => $node->value];
            $node = $node->next;
        }
        return $arr;
    }
}
