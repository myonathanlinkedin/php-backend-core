<?php
declare(strict_types=1);
require_once __DIR__ . '/types.php';

/**
 * Hopcroft–Karp algorithm for maximum bipartite matching.
 *
 * Time   : O(E·√V) where V = nLeft + nRight
 * Space  : O(V + E)
 */
final class HopcroftKarp
{
    private BipartiteGraph $graph;
    /** @var array<int, int> */
    private array $pairU;
    /** @var array<int, int> */
    private array $pairV;
    /** @var array<int, int> */
    private array $dist;

    public function __construct(BipartiteGraph $graph)
    {
        $this->graph = $graph;
        $this->pairU = array_fill(0, $graph->nLeft, -1);
        $this->pairV = array_fill(0, $graph->nRight, -1);
        $this->dist = [];
    }

    /**
     * Executes the algorithm and returns a MatchingResult.
     */
    public function maxMatching(): MatchingResult
    {
        $matching = 0;
        while ($this->bfs()) {
            for ($u = 0; $u < $this->graph->nLeft; ++$u) {
                if ($this->pairU[$u] === -1 && $this->dfs($u)) {
                    ++$matching;
                }
            }
        }
        return new MatchingResult($this->pairU, $this->pairV, $matching);
    }

    /**
     * Breadth‑first search building distance layers.
     *
     * @return bool true if at least one free right vertex is reachable.
     */
    private function bfs(): bool
    {
        $queue = new SplQueue();
        $this->dist = [];

        // Initialise distances for free left vertices.
        for ($u = 0; $u < $this->graph->nLeft; ++$u) {
            if ($this->pairU[$u] === -1) {
                $this->dist[$u] = 0;
                $queue->enqueue($u);
            } else {
                $this->dist[$u] = -1;
            }
        }

        $foundFreeRight = false;

        while (!$queue->isEmpty()) {
            $u = $queue->dequeue();
            foreach ($this->graph->adj[$u] as $v) {
                $matchedU = $this->pairV[$v];
                if ($matchedU !== -1) {
                    if ($this->dist[$matchedU] === -1) {
                        $this->dist[$matchedU] = $this->dist[$u] + 1;
                        $queue->enqueue($matchedU);
                    }
                } else {
                    $foundFreeRight = true;
                }
            }
        }

        return $foundFreeRight;
    }

    /**
     * Depth‑first search for an augmenting path starting at left vertex $u.
     *
     * @param int $u Left vertex index.
     * @return bool true if an augmenting path was found.
     */
    private function dfs(int $u): bool
    {
        foreach ($this->graph->adj[$u] as $v) {
            $matchedU = $this->pairV[$v];
            if ($matchedU === -1 ||
                ($this->dist[$matchedU] === $this->dist[$u] + 1 && $this->dfs($matchedU))) {
                $this->pairU[$u] = $v;
                $this->pairV[$v] = $u;
                return true;
            }
        }
        $this->dist[$u] = -1; // dead end for current layering
        return false;
    }
}
?>
