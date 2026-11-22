<?php

abstract class Node {
    public array $keys = [];
    public bool $isLeaf;
    public function __construct(bool $isLeaf) {
        $this->isLeaf = $isLeaf;
    }
}

class InternalNode extends Node {
    public array $children = [];
    public function __construct() {
        parent::__construct(false);
    }
}

class LeafNode extends Node {
    public array $values = [];
    public ?LeafNode $next = null;
    public function __construct() {
        parent::__construct(true);
    }
}

class BPlusTree {
    private int $order;
    private Node $root;
    public function __construct(int $order = 4) {
        $this->order = $order;
        $this->root = new LeafNode();
    }

    public function search(int $key): ?int {
        $node = $this->root;
        while (!$node->isLeaf) {
            $i = 0;
            while ($i < count($node->keys) && $key >= $node->keys[$i]) {
                $i++;
            }
            $node = $node->children[$i];
        }
        $i = 0;
        while ($i < count($node->keys) && $key > $node->keys[$i]) {
            $i++;
        }
        if ($i < count($node->keys) && $key === $node->keys[$i]) {
            return $node->values[$i];
        }
        return null;
    }

    public function insert(int $key, int $value): void {
        $leaf = $this->findLeaf($key);
        $i = 0;
        while ($i < count($leaf->keys) && $key > $leaf->keys[$i]) {
            $i++;
        }
        if ($i < count($leaf->keys) && $key === $leaf->keys[$i]) {
            $leaf->values[$i] = $value;
            return;
        }
        array_splice($leaf->keys, $i, 0, [$key]);
        array_splice($leaf->values, $i, 0, [$value]);
        if (count($leaf->keys) > $this->order - 1) {
            $this->splitLeaf($leaf);
        }
    }

    private function findLeaf(int $key): LeafNode {
        $node = $this->root;
        while (!$node->isLeaf) {
            $i = 0;
            while ($i < count($node->keys) && $key >= $node->keys[$i]) {
                $i++;
            }
            $node = $node->children[$i];
        }
        return $node;
    }

    private function splitLeaf(LeafNode $leaf): void {
        $mid = intdiv(count($leaf->keys), 2);
        $right = new LeafNode();
        $right->keys = array_slice($leaf->keys, $mid);
        $right->values = array_slice($leaf->values, $mid);
        $leaf->keys = array_slice($leaf->keys, 0, $mid);
        $leaf->values = array_slice($leaf->values, 0, $mid);
        $right->next = $leaf->next;
        $leaf->next = $right;
        $this->insertIntoParent($leaf, $right->keys[0], $right);
    }

    private function insertIntoParent(Node $left, int $key, Node $right): void {
        if ($left === $this->root) {
            $newRoot = new InternalNode();
            $newRoot->keys = [$key];
            $newRoot->children = [$left, $right];
            $this->root = $newRoot;
            return;
        }
        $parent = $this->findParent($this->root, $left);
        if ($parent === null) {
            return;
        }
        $i = 0;
        while ($i < count($parent->keys) && $left !== $parent->children[$i]) {
            $i++;
        }
        array_splice($parent->keys, $i, 0, [$key]);
        array_splice($parent->children, $i + 1, 0, [$right]);
        if (count($parent->keys) > $this->order - 1) {
            $this->splitInternal($parent);
        }
    }

    private function splitInternal(InternalNode $node): void {
        $mid = intdiv(count($node->keys), 2);
        $right = new InternalNode();
        $right->keys = array_slice($node->keys, $mid + 1);
        $right->children = array_slice($node->children, $mid + 1);
        $upKey = $node->keys[$mid];
        $node->keys = array_slice($node->keys, 0, $mid);
        $node->children = array_slice($node->children, 0, $mid + 1);
        $this->insertIntoParent($node, $upKey, $right);
    }

    private function findParent(Node $current, Node $child): ?InternalNode {
        if ($current->isLeaf || $current->children[0]->isLeaf) {
            return null;
        }
        foreach ($current->children as $c) {
            if ($c === $child) {
                return $current;
            }
            if (!$c->isLeaf) {
                $res = $this->findParent($c, $child);
                if ($res !== null) {
                    return $res;
                }
            }
        }
        return null;
    }

    public function rangeSearch(int $start, int $end): array {
        $result = [];
        $node = $this->findLeaf($start);
        while ($node !== null) {
            foreach ($node->keys as $i => $k) {
                if ($k >= $start && $k <= $end) {
                    $result[] = [$k, $node->values[$i]];
                } elseif ($k > $end) {
                    return $result;
                }
            }
            $node = $node->next;
        }
        return $result;
    }
}
