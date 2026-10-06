<?php

class Node {
    public int $value;
    public ?Node $next = null;

    public function __construct(int $value) {
        $this->value = $value;
    }
}

class LinkedList {
    private ?Node $head = null;
    private ?Node $tail = null;
    private int $size = 0;

    public function add(int $value): void {
        $node = new Node($value);
        if ($this->head === null) {
            $this->head = $node;
            $this->tail = $node;
        } else {
            $this->tail->next = $node;
            $this->tail = $node;
        }
        $this->size++;
    }

    public function remove(int $value): bool {
        $prev = null;
        $current = $this->head;
        while ($current !== null) {
            if ($current->value === $value) {
                if ($prev === null) {
                    $this->head = $current->next;
                } else {
                    $prev->next = $current->next;
                }
                if ($current === $this->tail) {
                    $this->tail = $prev;
                }
                $this->size--;
                return true;
            }
            $prev = $current;
            $current = $current->next;
        }
        return false;
    }

    public function find(int $value): ?Node {
        $current = $this->head;
        while ($current !== null) {
            if ($current->value === $value) {
                return $current;
            }
            $current = $current->next;
        }
        return null;
    }

    public function toArray(): array {
        $arr = [];
        $current = $this->head;
        while ($current !== null) {
            $arr[] = $current->value;
            $current = $current->next;
        }
        return $arr;
    }

    public function size(): int {
        return $this->size;
    }
}
