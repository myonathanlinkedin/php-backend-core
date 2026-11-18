<?php

class BTreeNode {
    public array $keys = [];
    public array $children = [];
    public bool $leaf;
    public int $t;

    public function __construct(int $t, bool $leaf) {
        $this->t = $t;
        $this->leaf = $leaf;
    }

    public function search(string $k): ?int {
        $i = 0;
        while ($i < count($this->keys) && strcmp($k, $this->keys[$i]) > 0) {
            $i++;
        }
        if ($i < count($this->keys) && $k === $this->keys[$i]) {
            return $i;
        }
        if ($this->leaf) {
            return null;
        }
        return $this->children[$i]->search($k);
    }

    public function insertNonFull(string $k): void {
        $i = count($this->keys) - 1;
        if ($this->leaf) {
            while ($i >= 0 && strcmp($k, $this->keys[$i]) < 0) {
                $i--;
            }
            array_splice($this->keys, $i + 1, 0, [$k]);
        } else {
            while ($i >= 0 && strcmp($k, $this->keys[$i]) < 0) {
                $i--;
            }
            $i++;
            if (count($this->children[$i]->keys) == 2 * $this->t - 1) {
                $this->splitChild($i);
                if (strcmp($k, $this->keys[$i]) > 0) {
                    $i++;
                }
            }
            $this->children[$i]->insertNonFull($k);
        }
    }

    public function splitChild(int $i): void {
        $t = $this->t;
        $y = $this->children[$i];
        $z = new BTreeNode($t, $y->leaf);
        $midKey = $y->keys[$t - 1];
        $z->keys = array_slice($y->keys, $t);
        $y->keys = array_slice($y->keys, 0, $t - 1);
        if (!$y->leaf) {
            $z->children = array_slice($y->children, $t);
            $y->children = array_slice($y->children, 0, $t);
        }
        array_splice($this->children, $i + 1, 0, [$z]);
        array_splice($this->keys, $i, 0, [$midKey]);
    }
}

class BTree {
    private BTreeNode $root;
    private int $t;

    public function __construct(int $t = 2) {
        $this->t = $t;
        $this->root = new BTreeNode($t, true);
    }

    public function search(string $k): ?int {
        return $this->root->search($k);
    }

    public function insert(string $k): void {
        $r = $this->root;
        if (count($r->keys) == 2 * $this->t - 1) {
            $s = new BTreeNode($this->t, false);
            $s->children = [$r];
            $s->splitChild(0);
            $this->root = $s;
            $s->insertNonFull($k);
        } else {
            $r->insertNonFull($k);
        }
    }
}

class LSMTree {
    private array $memtable = [];
    private array $sstables = [];
    private int $flushThreshold;

    public function __construct(int $flushThreshold = 4) {
        $this->flushThreshold = $flushThreshold;
    }

    public function insert(string $k, string $v): void {
        $this->memtable[$k] = $v;
        if (count($this->memtable) >= $this->flushThreshold) {
            $this->flush();
        }
    }

    private function flush(): void {
        ksort($this->memtable);
        $this->sstables[] = $this->memtable;
        $this->memtable = [];
    }

    public function search(string $k): ?string {
        if (array_key_exists($k, $this->memtable)) {
            return $this->memtable[$k];
        }
        for ($i = count($this->sstables) - 1; $i >= 0; $i--) {
            $table = $this->sstables[$i];
            if (array_key_exists($k, $table)) {
                return $table[$k];
            }
        }
        return null;
    }

    public function merge(): void {
        $merged = [];
        foreach ($this->sstables as $table) {
            foreach ($table as $k => $v) {
                $merged[$k] = $v;
            }
        }
        ksort($merged);
        $this->sstables = [$merged];
    }
}
