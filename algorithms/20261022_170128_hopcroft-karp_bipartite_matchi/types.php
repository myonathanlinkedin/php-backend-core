<?php
declare(strict_types=1);

/**
 * Representation of a bipartite graph G = (U, V, E)
 * where U = {1..$nLeft}, V = {1..$nRight}
 */
final class BipartiteGraph
{
    /** @var int Number of vertices on the left side (U) */
    public int $nLeft;

    /** @var int Number of vertices on the right side (V) */
    public int $nRight;

    /**
     * @var array<int, array<int>> adjacency list from U to V
     * Example: $adj[2] = [3,5] means edges (2,3) and (2,5)
     */
    public array $adj;

    /**
     * @param int $nLeft  number of left vertices (must be >= 0)
     * @param int $nRight number of right vertices (must be >= 0)
     */
    public function __construct(int $nLeft, int $nRight)
    {
        if ($nLeft < 0 || $nRight < 0) {
            throw new InvalidArgumentException('Vertex counts must be non‑negative.');
        }
        $this->nLeft  = $nLeft;
        $this->nRight = $nRight;
        $this->adj    = [];

        // Initialise empty adjacency lists for each left vertex
        for ($u = 1; $u <= $nLeft; $u++) {
            $this->adj[$u] = [];
        }
    }

    /**
     * Adds an undirected edge (u, v) where u ∈ U and v ∈ V.
     *
     * @param int $u left‑side vertex identifier (1‑based)
     * @param int $v right‑side vertex identifier (1‑based)
     */
    public function addEdge(int $u, int $v): void
    {
        if ($u < 1 || $u > $this->nLeft) {
            throw new OutOfBoundsException('Left vertex out of range.');
        }
        if ($v < 1 || $v > $this->nRight) {
            throw new OutOfBoundsException('Right vertex out of range.');
        }
        $this->adj[$u][] = $v;
    }
}
