<?php
declare(strict_types=1);

class HopcroftKarp
{
    /** @var array<int, array<int>> adjacency list from left vertices to right vertices */
    private array $adj;
    private int $nLeft;
    private int $nRight;
    /** @var array<int> matching for left side, -1 if free */
    private array $pairU;
    /** @var array<int> matching for right side, -1 if free */
    private array $pairV;
    /** @var array<int> distances used in BFS */
    private array $dist;

    public function __construct(array $adj, int $nLeft, int $nRight)
    {
        $this->adj = $adj;
        $this->nLeft = $nLeft;
        $this->nRight = $nRight;
        $this->pairU = array_fill(0, $nLeft, -1);
        $this->pairV = array_fill(0, $nRight, -1);
        $this->dist = array_fill(0, $nLeft, 0);
    }

    /** @return int size of maximum matching */
    public function maxMatching(): int
    {
        $matching = 0;
        while ($this->bfs()) {
            for ($u = 0; $u < $this->nLeft; $u++) {
                if ($this->pairU[$u] === -1 && $this->dfs($u)) {
                    $matching++;
                }
            }
        }
        return $matching;
    }

    /** @return array<int> matching for left vertices (index => right vertex or -1) */
    public function getMatchingLeft(): array
    {
        return $this->pairU;
    }

    /** @return array<int> matching for right vertices (index => left vertex or -1) */
    public function getMatchingRight(): array
    {
        return $this->pairV;
    }

    /** BFS builds distance layers; returns true if there is an augmenting path */
    private function bfs(): bool
    {
        $queue = new SplQueue();
        $INF = PHP_INT_MAX;

        for ($u = 0; $u < $this->nLeft; $u++) {
            if ($this->pairU[$u] === -1) {
                $this->dist[$u] = 0;
                $queue->enqueue($u);
            } else {
                $this->dist[$u] = $INF;
            }
        }

        $distFound = $INF;

        while (!$queue->isEmpty()) {
            $u = $queue->dequeue();
            if ($this->dist[$u] < $distFound) {
                foreach ($this->adj[$u] ?? [] as $v) {
                    $pairedU = $this->pairV[$v];
                    if ($pairedU === -1) {
                        $distFound = $this->dist[$u] + 1;
                    } elseif ($this->dist[$pairedU] === $INF) {
                        $this->dist[$pairedU] = $this->dist[$u] + 1;
                        $queue->enqueue($pairedU);
                    }
                }
            }
        }

        return $distFound !== $INF;
    }

    /** DFS searches for augmenting paths respecting BFS layers */
    private function dfs(int $u): bool
    {
        foreach ($this->adj[$u] ?? [] as $v) {
            $pairedU = $this->pairV[$v];
            if ($pairedU === -1 ||
                ($this->dist[$pairedU] === $this->dist[$u] + 1 && $this->dfs($pairedU))) {
                $this->pairU[$u] = $v;
                $this->pairV[$v] = $u;
                return true;
            }
        }
        $this->dist[$u] = PHP_INT_MAX;
        return false;
    }
}
