<?php
declare(strict_types=1);

final class Graph
{
    /** @var array<int, array<int, bool>> */
    private array $adj = [];

    public function addVertex(int $v): void
    {
        if (!isset($this->adj[$v])) {
            $this->adj[$v] = [];
        }
    }

    public function addEdge(int $u, int $v): void
    {
        $this->addVertex($u);
        $this->addVertex($v);
        $this->adj[$u][$v] = true;
        $this->adj[$v][$u] = true;
    }

    /** @return list<int> */
    public function getVertices(): array
    {
        return array_keys($this->adj);
    }

    /** @return list<int> */
    public function getNeighbors(int $v): array
    {
        return isset($this->adj[$v]) ? array_keys($this->adj[$v]) : [];
    }

    public function hasEdge(int $u, int $v): bool
    {
        return isset($this->adj[$u][$v]);
    }
}

/**
 * Simple bounded‑treedepth decomposition.
 * Produces a parent map and depth map respecting the given max depth.
 */
final class TreedepthDecomposition
{
    private Graph $graph;
    private int $maxDepth;
    /** @var array<int, ?int> */
    private array $parent = [];
    /** @var array<int, int> */
    private array $depth = [];

    /**
     * @param Graph $graph
     * @param int   $maxDepth Must be >= 1
     */
    public function __construct(Graph $graph, int $maxDepth)
    {
        if ($maxDepth < 1) {
            throw new InvalidArgumentException('maxDepth must be >= 1');
        }
        $this->graph = $graph;
        $this->maxDepth = $maxDepth;
        $this->buildDecomposition();
    }

    private function buildDecomposition(): void
    {
        $visited = [];
        foreach ($this->graph->getVertices() as $v) {
            if (isset($visited[$v])) {
                continue;
            }
            $this->dfs($v, null, 1, $visited);
        }
    }

    /**
     * Depth‑first search respecting depth limit.
     *
     * @param int      $v
     * @param int|null $parent
     * @param int      $depth
     * @param array    $visited
     */
    private function dfs(int $v, ?int $parent, int $depth, array &$visited): void
    {
        $visited[$v] = true;
        $this->parent[$v] = $parent;
        $this->depth[$v] = $depth;

        if ($depth >= $this->maxDepth) {
            // Children become new roots to keep depth bounded.
            foreach ($this->graph->getNeighbors($v) as $nbr) {
                if (!isset($visited[$nbr])) {
                    $this->dfs($nbr, null, 1, $visited);
                }
            }
            return;
        }

        foreach ($this->graph->getNeighbors($v) as $nbr) {
            if (!isset($visited[$nbr])) {
                $this->dfs($nbr, $v, $depth + 1, $visited);
            }
        }
    }

    public function getParent(int $v): ?int
    {
        return $this->parent[$v] ?? null;
    }

    public function getDepth(int $v): int
    {
        return $this->depth[$v] ?? 0;
    }

    /** @return list<int> */
    public function getRoots(): array
    {
        $roots = [];
        foreach ($this->graph->getVertices() as $v) {
            if ($this->parent[$v] === null) {
                $roots[] = $v;
            }
        }
        return $roots;
    }
}

/**
 * Solver for the Vertex‑Cover decision problem on bounded‑treedepth graphs.
 * Returns true iff a vertex cover of size ≤ $k exists.
 */
final class VertexCoverSolver
{
    /**
     * @param Graph $graph
     * @param int   $k        Desired cover size bound
     * @param int   $maxDepth Treedepth bound for the decomposition
     *
     * @return bool
     */
    public static function existsVertexCover(Graph $graph, int $k, int $maxDepth): bool
    {
        $decomp = new TreedepthDecomposition($graph, $maxDepth);
        $memo   = [];

        $total = 0;
        foreach ($decomp->getRoots() as $root) {
            $dp = self::dp($root, $graph, $decomp, $memo);
            $total += min($dp['taken'], $dp['notTaken']);
            if ($total > $k) {
                return false; // early exit
            }
        }
        return $total <= $k;
    }

    /**
     * Dynamic programming on the decomposition tree.
     *
     * @param int   $v
     * @param Graph $graph
     * @param TreedepthDecomposition $decomp
     * @param array $memo  Memoisation table keyed by vertex id
     *
     * @return array{taken:int, notTaken:int}
     */
    private static function dp(int $v, Graph $graph, TreedepthDecomposition $decomp, array &$memo): array
    {
        if (isset($memo[$v])) {
            return $memo[$v];
        }

        $taken    = 1; // we include v
        $notTaken = 0; // we exclude v

        foreach ($graph->getNeighbors($v) as $nbr) {
            if ($decomp->getParent($nbr) === $v) { // child in the decomposition
                $childDP = self::dp($nbr, $graph, $decomp, $memo);
                // If v is taken, child may be taken or not.
                $taken += min($childDP['taken'], $childDP['notTaken']);
                // If v is not taken, child must be taken to cover edge (v, nbr).
                $notTaken += $childDP['taken'];
            }
        }

        $memo[$v] = ['taken' => $taken, 'notTaken' => $notTaken];
        return $memo[$v];
    }
}
