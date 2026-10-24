<?php

interface GraphInterface
{
    /**
     * @return array<int> List of vertex identifiers.
     */
    public function getVertices(): array;

    /**
     * @param int $vertex
     * @return array<Edge> List of outgoing edges from the given vertex.
     */
    public function getEdgesFrom(int $vertex): array;
}

final class Edge
{
    public int $to;
    public int $weight;

    public function __construct(int $to, int $weight)
    {
        $this->to = $to;
        $this->weight = $weight;
    }
}

final class Graph implements GraphInterface
{
    /** @var array<int, array<Edge>> */
    private array $adjacencyList = [];

    public function addEdge(int $from, int $to, int $weight): void
    {
        $edge = new Edge($to, $weight);
        $this->adjacencyList[$from][] = $edge;
        // Ensure the destination vertex exists in the list even if it has no outgoing edges.
        if (!array_key_exists($to, $this->adjacencyList)) {
            $this->adjacencyList[$to] = [];
        }
    }

    public function getVertices(): array
    {
        return array_keys($this->adjacencyList);
    }

    public function getEdgesFrom(int $vertex): array
    {
        return $this->adjacencyList[$vertex] ?? [];
    }
}

final class FibonacciHeapNode
{
    public int $key;          // priority
    public int $value;        // vertex id
    public int $degree = 0;
    public bool $mark = false;
    public ?FibonacciHeapNode $parent = null;
    public ?FibonacciHeapNode $child = null;
    public FibonacciHeapNode $left;
    public FibonacciHeapNode $right;

    public function __construct(int $key, int $value)
    {
        $this->key = $key;
        $this->value = $value;
        $this->left = $this;
        $this->right = $this;
    }
}

final class FibonacciHeap
{
    private ?FibonacciHeapNode $min = null;
    private int $totalNodes = 0;

    public function insert(int $key, int $value): FibonacciHeapNode
    {
        $node = new FibonacciHeapNode($key, $value);
        if ($this->min === null) {
            $this->min = $node;
        } else {
            // Insert into root list
            $node->left = $this->min;
            $node->right = $this->min->right;
            $this->min->right->left = $node;
            $this->min->right = $node;
            if ($key < $this->min->key) {
                $this->min = $node;
            }
        }
        $this->totalNodes++;
        return $node;
    }

    public function extractMin(): ?FibonacciHeapNode
    {
        $z = $this->min;
        if ($z === null) {
            return null;
        }

        // Add children of z to root list
        if ($z->child !== null) {
            $children = [];
            $child = $z->child;
            do {
                $children[] = $child;
                $child = $child->right;
            } while ($child !== $z->child);

            foreach ($children as $c) {
                // Remove parent link
                $c->parent = null;
                // Add to root list
                $c->left = $this->min;
                $c->right = $this->min->right;
                $this->min->right->left = $c;
                $this->min->right = $c;
            }
        }

        // Remove z from root list
        if ($z->right === $z) {
            $this->min = null;
        } else {
            $z->left->right = $z->right;
            $z->right->left = $z->left;
            $this->min = $z->right;
            $this->consolidate();
        }

        $this->totalNodes--;
        return $z;
    }

    public function decreaseKey(FibonacciHeapNode $x, int $newKey): void
    {
        if ($newKey > $x->key) {
            throw new RuntimeException('New key is greater than current key.');
        }
        $x->key = $newKey;
        $y = $x->parent;
        if ($y !== null && $x->key < $y->key) {
            $this->cut($x, $y);
            $this->cascadingCut($y);
        }
        if ($x->key < $this->min->key) {
            $this->min = $x;
        }
    }

    public function isEmpty(): bool
    {
        return $this->min === null;
    }

    private function consolidate(): void
    {
        $maxDegree = (int)floor(log($this->totalNodes, 2)) + 1;
        $A = array_fill(0, $maxDegree + 1, null);

        // Build a list of root nodes
        $roots = [];
        $w = $this->min;
        if ($w !== null) {
            do {
                $roots[] = $w;
                $w = $w->right;
            } while ($w !== $this->min);
        }

        foreach ($roots as $w) {
            $x = $w;
            $d = $x->degree;
            while ($A[$d] !== null) {
                $y = $A[$d];
                if ($x->key > $y->key) {
                    $tmp = $x;
                    $x = $y;
                    $y = $tmp;
                }
                $this->link($y, $x);
                $A[$d] = null;
                $d++;
            }
            $A[$d] = $x;
        }

        // Reconstruct the root list and find new min
        $this->min = null;
        foreach ($A as $node) {
            if ($node !== null) {
                if ($this->min === null) {
                    $node->left = $node;
                    $node->right = $node;
                    $this->min = $node;
                } else {
                    // Insert node into root list
                    $node->left = $this->min;
                    $node->right = $this->min->right;
                    $this->min->right->left = $node;
                    $this->min->right = $node;
                    if ($node->key < $this->min->key) {
                        $this->min = $node;
                    }
                }
            }
        }
    }

    private function link(FibonacciHeapNode $y, FibonacciHeapNode $x): void
    {
        // Remove y from root list
        $y->left->right = $y->right;
        $y->right->left = $y->left;

        // Make y a child of x
        $y->parent = $x;
        if ($x->child === null) {
            $x->child = $y;
            $y->left = $y;
            $y->right = $y;
        } else {
            $y->left = $x->child;
            $y->right = $x->child->right;
            $x->child->right->left = $y;
            $x->child->right = $y;
        }
        $x->degree++;
        $y->mark = false;
    }

    private function cut(FibonacciHeapNode $x, FibonacciHeapNode $y): void
    {
        // Remove x from child list of y
        if ($x->right === $x) {
            $y->child = null;
        } else {
            $x->right->left = $x->left;
            $x->left->right = $x->right;
            if ($y->child === $x) {
                $y->child = $x->right;
            }
        }
        $y->degree--;

        // Add x to root list
        $x->left = $this->min;
        $x->right = $this->min->right;
        $this->min->right->left = $x;
        $this->min->right = $x;
        $x->parent = null;
        $x->mark = false;
    }

    private function cascadingCut(FibonacciHeapNode $y): void
    {
        $z = $y->parent;
        if ($z !== null) {
            if ($y->mark === false) {
                $y->mark = true;
            } else {
                $this->cut($y, $z);
                $this->cascadingCut($z);
            }
        }
    }
}
