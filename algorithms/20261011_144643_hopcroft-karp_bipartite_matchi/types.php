<?php
declare(strict_types=1);

/**
 * Simple representation of a bipartite graph.
 */
final class BipartiteGraph
{
    /** @var int Number of vertices on the left side (U) */
    public int $nLeft;
    /** @var int Number of vertices on the right side (V) */
    public int $nRight;
    /** @var array<int, array<int>> Adjacency list from left to right */
    public array $adj;

    public function __construct(int $nLeft, int $nRight)
    {
        if ($nLeft < 0 || $nRight < 0) {
            throw new InvalidArgumentException('Number of vertices must be non‑negative');
        }
        $this->nLeft = $nLeft;
        $this->nRight = $nRight;
        $this->adj = array_fill(0, $nLeft, []);
    }

    /**
     * Adds an edge from left vertex $u to right vertex $v.
     *
     * @param int $u Index in [0, $nLeft‑1]
     * @param int $v Index in [0, $nRight‑1]
     */
    public function addEdge(int $u, int $v): void
    {
        if ($u < 0 || $u >= $this->nLeft) {
            throw new OutOfBoundsException('Left vertex out of range');
        }
        if ($v < 0 || $v >= $this->nRight) {
            throw new OutOfBoundsException('Right vertex out of range');
        }
        $this->adj[$u][] = $v;
    }
}

/**
 * Container for a maximum matching result.
 */
final class MatchingResult
{
    /** @var array<int, int> Mapping left → right, -1 if unmatched */
    public array $pairU;
    /** @var array<int, int> Mapping right → left, -1 if unmatched */
    public array $pairV;
    /** @var int Size of the matching */
    public int $size;

    public function __construct(array $pairU, array $pairV, int $size)
    {
        $this->pairU = $pairU;
        $this->pairV = $pairV;
        $this->size = $size;
    }
}
?>
