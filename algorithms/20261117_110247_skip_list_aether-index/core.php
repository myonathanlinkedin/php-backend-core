<?php
declare(strict_types=1);

class SkipListNode
{
    public int $key;
    public $value;
    /** @var array<int, ?SkipListNode> */
    public array $forward;

    public function __construct(int $key, $value, int $level)
    {
        $this->key = $key;
        $this->value = $value;
        $this->forward = array_fill(0, $level + 1, null);
    }
}

class SkipList
{
    private int $maxLevel;
    private float $p;
    private int $level = 0;
    private SkipListNode $header;

    public function __construct(int $maxLevel = 16, float $p = 0.5)
    {
        $this->maxLevel = $maxLevel;
        $this->p = $p;
        $this->header = new SkipListNode(PHP_INT_MIN, null, $maxLevel);
    }

    private function randomLevel(): int
    {
        $lvl = 0;
        while (mt_rand() / mt_getrandmax() < $this->p && $lvl < $this->maxLevel) {
            $lvl++;
        }
        return $lvl;
    }

    public function insert(int $key, $value): void
    {
        $update = array_fill(0, $this->maxLevel + 1, $this->header);
        $x = $this->header;
        for ($i = $this->level; $i >= 0; $i--) {
            while ($x->forward[$i] !== null && $x->forward[$i]->key < $key) {
                $x = $x->forward[$i];
            }
            $update[$i] = $x;
        }
        $x = $x->forward[0];
        if ($x !== null && $x->key === $key) {
            $x->value = $value;
            return;
        }
        $lvl = $this->randomLevel();
        if ($lvl > $this->level) {
            for ($i = $this->level + 1; $i <= $lvl; $i++) {
                $update[$i] = $this->header;
            }
            $this->level = $lvl;
        }
        $newNode = new SkipListNode($key, $value, $lvl);
        for ($i = 0; $i <= $lvl; $i++) {
            $newNode->forward[$i] = $update[$i]->forward[$i];
            $update[$i]->forward[$i] = $newNode;
        }
    }

    public function delete(int $key): bool
    {
        $update = array_fill(0, $this->maxLevel + 1, $this->header);
        $x = $this->header;
        for ($i = $this->level; $i >= 0; $i--) {
            while ($x->forward[$i] !== null && $x->forward[$i]->key < $key) {
                $x = $x->forward[$i];
            }
            $update[$i] = $x;
        }
        $x = $x->forward[0];
        if ($x === null || $x->key !== $key) {
            return false;
        }
        for ($i = 0; $i <= $this->level; $i++) {
            if ($update[$i]->forward[$i] !== $x) {
                break;
            }
            $update[$i]->forward[$i] = $x->forward[$i];
        }
        while ($this->level > 0 && $this->header->forward[$this->level] === null) {
            $this->level--;
        }
        return true;
    }

    public function search(int $key)
    {
        $x = $this->header;
        for ($i = $this->level; $i >= 0; $i--) {
            while ($x->forward[$i] !== null && $x->forward[$i]->key < $key) {
                $x = $x->forward[$i];
            }
        }
        $x = $x->forward[0];
        if ($x !== null && $x->key === $key) {
            return $x->value;
        }
        return null;
    }

    /**
     * @return array<int, mixed>
     */
    public function range(int $startKey, int $endKey): array
    {
        $result = [];
        $x = $this->header;
        for ($i = $this->level; $i >= 0; $i--) {
            while ($x->forward[$i] !== null && $x->forward[$i]->key < $startKey) {
                $x = $x->forward[$i];
            }
        }
        $x = $x->forward[0];
        while ($x !== null && $x->key <= $endKey) {
            $result[$x->key] = $x->value;
            $x = $x->forward[0];
        }
        return $result;
    }

    public function getCurrentLevel(): int
    {
        return $this->level;
    }
}
