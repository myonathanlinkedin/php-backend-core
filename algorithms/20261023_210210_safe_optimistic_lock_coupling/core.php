<?php

class Node
{
    public int $key;
    public mixed $value;
    public ?Node $next;
    public int $version;
    public bool $locked;

    public function __construct(int $key, mixed $value = null, ?Node $next = null)
    {
        $this->key = $key;
        $this->value = $value;
        $this->next = $next;
        $this->version = 0;
        $this->locked = false;
    }
}

class OptimisticLinkedList
{
    private Node $head;

    public function __construct()
    {
        $this->head = new Node(php_int_min());
    }

    private function lock(Node $node): void
    {
        $node->locked = true;
    }

    private function unlock(Node $node): void
    {
        $node->locked = false;
    }

    private function validate(Node $pred, Node $curr): bool
    {
        return !$pred->locked && !$curr->locked && $pred->next === $curr;
    }

    public function find(int $key): ?Node
    {
        $pred = $this->head;
        $curr = $pred->next;
        while ($curr !== null && $curr->key < $key) {
            $pred = $curr;
            $curr = $curr->next;
        }
        return $curr;
    }

    public function insert(int $key, mixed $value): bool
    {
        while (true) {
            $pred = $this->head;
            $curr = $pred->next;
            while ($curr !== null && $curr->key < $key) {
                $pred = $curr;
                $curr = $curr->next;
            }
            if ($curr !== null && $curr->key === $key) {
                return false;
            }
            $newNode = new Node($key, $value, $curr);
            $this->lock($pred);
            $this->lock($curr);
            if ($this->validate($pred, $curr)) {
                $pred->next = $newNode;
                $pred->version++;
                $this->unlock($pred);
                $this->unlock($curr);
                return true;
            }
            $this->unlock($pred);
            $this->unlock($curr);
        }
    }

    public function delete(int $key): bool
    {
        while (true) {
            $pred = $this->head;
            $curr = $pred->next;
            while ($curr !== null && $curr->key < $key) {
                $pred = $curr;
                $curr = $curr->next;
            }
            if ($curr === null || $curr->key !== $key) {
                return false;
            }
            $this->lock($pred);
            $this->lock($curr);
            if ($this->validate($pred, $curr)) {
                $pred->next = $curr->next;
                $pred->version++;
                $this->unlock($pred);
                $this->unlock($curr);
                return true;
            }
            $this->unlock($pred);
            $this->unlock($curr);
        }
    }

    public function contains(int $key): bool
    {
        $curr = $this->head->next;
        while ($curr !== null && $curr->key < $key) {
            $curr = $curr->next;
        }
        return $curr !== null && $curr->key === $key;
    }

    public function toArray(): array
    {
        $arr = [];
        $curr = $this->head->next;
        while ($curr !== null) {
            $arr[] = ['key' => $curr->key, 'value' => $curr->value];
            $curr = $curr->next;
        }
        return $arr;
    }
}
