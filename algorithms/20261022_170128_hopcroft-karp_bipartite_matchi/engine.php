<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Hopcroft–Karp algorithm for maximum cardinality matching
 * in bipartite graphs. Runs in O(E·√V) time.
 */
final class HopcroftKarp
{
    private const NIL = 0;
    private const INF = PHP_INT_MAX;

    private BipartiteGraph $graph;
    /** @var array<int, int> pairU[u] = matched v or NIL */
    private array $pairU;
    /** @var array<int, int> pairV[v] = matched u or NIL */
    private array $pairV;
    /** @var array<int, int> dist[u] distance in BFS layering */
    private array $dist;

    public function __construct(BipartiteGraph $graph)
    {
        $this->graph = $graph;
        $this->pairU = array_fill(0, $graph->nLeft + 1, self::NIL);
        $this->pairV = array_fill(0, $graph->nRight + 1, self::NIL);
        $this->dist  = array_fill(0, $graph->nLeft + 1, 0);
    }

    /**
     * Computes the size of a maximum matching.
     *
     * @return int cardinality of the maximum matching
     */
    public function maxMatching(): int
    {
        $matching = 0;
        while ($this->bfs()) {
            for ($u = 1; $u <= $this->graph->nLeft; $u++) {
                if ($this->pairU[$u] === self::NIL) {
                    if ($this->dfs($u)) {
                        $matching++;
                    }
                }
            }
        }
        return $matching;
    }

    /**
     * Breadth‑first search that builds distance layers.
     *
     * @return bool true if there exists an augmenting path
     */
    private function bfs(): bool
    {
        $queue = new SplQueue();

        for ($u = 1; $u <= $this->graph->nLeft; $u++) {
            if ($this->pairU[$u] === self::NIL) {
                $this->dist[$u] = 0;
                $queue->enqueue($u);
            } else {
                $this->dist[$u] = self::INF;
            }
        }
        $this->dist[self::NIL] = self::INF;

        while (!$queue->isEmpty()) {
            $u = $queue->dequeue();

            if ($this->dist[$u] < $this->dist[self::NIL]) {
                foreach ($this->graph->adj[$u] ?? [] as $v) {
                    $pairedU = $this->pairV[$v];
                    if ($this->dist[$pairedU] === self::INF) {
                        $this->dist[$pairedU] = $this->dist[$u] + 1;
                        $queue->enqueue($pairedU);
                    }
                }
            }
        }

        return $this->dist[self::NIL] !== self::INF;
    }

    /**
     * Depth‑first search that searches for augmenting paths
     * respecting the layering built by bfs().
     *
     * @param int $u current left‑side vertex
     * @return bool true if an augmenting path starting at $u is found
     */
    private function dfs(int $u): bool
    {
        if ($u !== self::NIL) {
            foreach ($this->graph->adj[$u] ?? [] as $v) {
                $pairedU = $this->pairV[$v];
                if (
                    $this->dist[$pairedU] === $this->dist[$u] + 1
                    && $this->dfs($pairedU)
                ) {
                    $this->pairV[$v] = $u;
                    $this->pairU[$u] = $v;
                    return true;
                }
            }
            $this->dist[$u] = self::INF;
            return false;
        }
        return true;
    }
}
