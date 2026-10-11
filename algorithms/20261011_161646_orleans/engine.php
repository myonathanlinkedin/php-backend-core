<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Production‑grade Union-Find implementation (also known as Disjoint Set Union).
 *
 * Time Complexity:
 *   - find():   α(N) amortized (inverse Ackermann)
 *   - union():  α(N) amortized
 *   - connected(): α(N) amortized
 *
 * Space Complexity: O(N) for parent and rank arrays.
 *
 * The class is deliberately immutable with respect to its public API – all state
 * changes happen through the defined methods, and internal invariants are enforced
 * via assertions and exceptions.
 */
final class UnionFind implements UnionFindInterface
{
    /** @var array<int,int> Parent links; parent[i] = i for roots. */
    private array $parent;

    /** @var array<int,int> Approximate tree depth (rank). */
    private array $rank;

    /** @var int Number of distinct components. */
    private int $components;

    public function __construct(int $size)
    {
        if ($size < 0) {
            throw new InvalidArgumentException('Size must be non‑negative');
        }

        $this->parent = [];
        $this->rank   = [];
        for ($i = 0; $i < $size; $i++) {
            $this->parent[$i] = $i;
            $this->rank[$i]   = 0;
        }
        $this->components = $size;
    }

    public function find(int $x): int
    {
        $this->assertValidIndex($x);
        // Path compression (recursive)
        if ($this->parent[$x] !== $x) {
            $this->parent[$x] = $this->find($this->parent[$x]);
        }
        return $this->parent[$x];
    }

    public function union(int $a, int $b): void
    {
        $rootA = $this->find($a);
        $rootB = $this->find($b);

        if ($rootA === $rootB) {
            return; // already in the same component
        }

        // Union by rank
        if ($this->rank[$rootA] < $this->rank[$rootB]) {
            $this->parent[$rootA] = $rootB;
        } elseif ($this->rank[$rootA] > $this->rank[$rootB]) {
            $this->parent[$rootB] = $rootA;
        } else {
            $this->parent[$rootB] = $rootA;
            $this->rank[$rootA]++;
        }

        $this->components--;
    }

    public function connected(int $a, int $b): bool
    {
        return $this->find($a) === $this->find($b);
    }

    public function getComponentCount(): int
    {
        return $this->components;
    }

    /**
     * Internal helper to validate element indices.
     *
     * @param int $idx
     * @throws OutOfBoundsException
     */
    private function assertValidIndex(int $idx): void
    {
        if (!array_key_exists($idx, $this->parent)) {
            throw new OutOfBoundsException("Index {$idx} is out of bounds");
        }
    }
}
