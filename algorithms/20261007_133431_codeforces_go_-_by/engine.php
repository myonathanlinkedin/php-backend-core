<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Union-Find implementation with union by rank and path compression.
 */
final class UnionFind implements SetUnionFind
{
    /**
     * @var array<int,int> Parent links.
     */
    private array $parent;

    /**
     * @var array<int,int> Rank (approximate tree depth).
     */
    private array $rank;

    /**
     * Initializes $size disjoint sets (0 … $size-1).
     *
     * @param int $size Number of elements.
     */
    public function __construct(int $size)
    {
        if ($size < 0) {
            throw new InvalidArgumentException('Size must be non‑negative');
        }
        $this->parent = range(0, $size - 1);
        $this->rank   = array_fill(0, $size, 0);
    }

    public function find(int $element): int
    {
        $this->assertValidElement($element);
        // Path compression (recursive).
        if ($this->parent[$element] !== $element) {
            $this->parent[$element] = $this->find($this->parent[$element]);
        }
        return $this->parent[$element];
    }

    public function union(int $a, int $b): void
    {
        $rootA = $this->find($a);
        $rootB = $this->find($b);

        if ($rootA === $rootB) {
            return;
        }

        // Union by rank.
        if ($this->rank[$rootA] < $this->rank[$rootB]) {
            $this->parent[$rootA] = $rootB;
        } elseif ($this->rank[$rootA] > $this->rank[$rootB]) {
            $this->parent[$rootB] = $rootA;
        } else {
            $this->parent[$rootB] = $rootA;
            $this->rank[$rootA]++;
        }
    }

    public function connected(int $a, int $b): bool
    {
        return $this->find($a) === $this->find($b);
    }

    /**
     * @param int $element
     * @return void
     */
    private function assertValidElement(int $element): void
    {
        if (!array_key_exists($element, $this->parent)) {
            throw new OutOfBoundsException("Element {$element} is out of bounds");
        }
    }

    /**
     * Returns a snapshot of the internal parent array (useful for testing).
     *
     * @return array<int,int>
     */
    public function getParentArray(): array
    {
        return $this->parent;
    }
}
