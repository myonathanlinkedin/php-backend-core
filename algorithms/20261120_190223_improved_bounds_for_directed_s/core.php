<?php
declare(strict_types=1);

/**
 * Edge representation for a directed weighted graph.
 */
final class Edge
{
    public int $to;
    public float $weight;

    public function __construct(int $to, float $weight)
    {
        $this->to = $to;
        $this->weight = $weight;
    }
}

/**
 * Directed graph with non‑negative edge weights.
 * Stores adjacency lists internally.
 */
final class Graph
{
    /** @var int Number of vertices (0‑based indexing) */
    private int $vertexCount;

    /** @var array<int, Edge[]> Adjacency list */
    private array $adj;

    public function __construct(int $vertexCount)
    {
        if ($vertexCount < 0) {
            throw new InvalidArgumentException('Vertex count must be non‑negative');
        }
        $this->vertexCount = $vertexCount;
        $this->adj = array_fill(0, $vertexCount, []);
    }

    public function getVertexCount(): int
    {
        return $this->vertexCount;
    }

    /**
     * Adds a directed edge u → v with given non‑negative weight.
     *
     * @throws InvalidArgumentException if vertices are out of range or weight is negative.
     */
    public function addEdge(int $from, int $to, float $weight): void
    {
        if ($from < 0 || $from >= $this->vertexCount ||
            $to < 0   || $to   >= $this->vertexCount) {
            throw new InvalidArgumentException('Vertex index out of bounds');
        }
        if ($weight < 0.0) {
            throw new InvalidArgumentException('Edge weight must be non‑negative');
        }
        $this->adj[$from][] = new Edge($to, $weight);
    }

    /**
     * @return Edge[]
     */
    public function getAdjacency(int $vertex): array
    {
        if ($vertex < 0 || $vertex >= $this->vertexCount) {
            throw new InvalidArgumentException('Vertex index out of bounds');
        }
        return $this->adj[$vertex];
    }

    /**
     * Returns the total number of edges in the graph.
     */
    public function edgeCount(): int
    {
        $cnt = 0;
        foreach ($this->adj as $list) {
            $cnt += count($list);
        }
        return $cnt;
    }

    /**
     * Single‑Source Shortest Paths using a binary min‑heap (SplPriorityQueue).
     * Runs in O((V + E) log V) for sparse graphs.
     *
     * @param int $source Source vertex (0‑based).
     * @return float[] Distance array where distance[i] = shortest distance from source to i,
     *                 or INF if i is unreachable.
     */
    public function dijkstraHeap(int $source): array
    {
        $V = $this->vertexCount;
        $dist = array_fill(0, $V, INF);
        $dist[$source] = 0.0;

        // SplPriorityQueue is a max‑heap; invert priority to simulate min‑heap.
        $pq = new SplPriorityQueue();
        $pq->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
        $pq->insert($source, -0.0);

        while (!$pq->isEmpty()) {
            $extracted = $pq->extract();
            $u = $extracted['data'];
            $curDist = -$extracted['priority']; // revert inversion

            // Skip stale entries.
            if ($curDist > $dist[$u]) {
                continue;
            }

            foreach ($this->adj[$u] as $edge) {
                $v = $edge->to;
                $alt = $dist[$u] + $edge->weight;
                if ($alt < $dist[$v]) {
                    $dist[$v] = $alt;
                    $pq->insert($v, -$alt);
                }
            }
        }

        return $dist;
    }

    /**
     * Dense‑graph variant of Dijkstra (no heap). Runs in O(V²).
     *
     * @param int $source Source vertex.
     * @return float[] Distance array.
     */
    public function dijkstraArray(int $source): array
    {
        $V = $this->vertexCount;
        $dist = array_fill(0, $V, INF);
        $visited = array_fill(0, $V, false);
        $dist[$source] = 0.0;

        for ($i = 0; $i < $V; ++$i) {
            // Find the unvisited vertex with the smallest tentative distance.
            $u = -1;
            $minDist = INF;
            for ($v = 0; $v < $V; ++$v) {
                if (!$visited[$v] && $dist[$v] < $minDist) {
                    $minDist = $dist[$v];
                    $u = $v;
                }
            }

            if ($u === -1) {
                break; // Remaining vertices are unreachable.
            }

            $visited[$u] = true;

            foreach ($this->adj[$u] as $edge) {
                $v = $edge->to;
                $alt = $dist[$u] + $edge->weight;
                if ($alt < $dist[$v]) {
                    $dist[$v] = $alt;
                }
            }
        }

        return $dist;
    }

    /**
     * Adaptive SSSP that selects the algorithm based on graph density.
     *
     * Density heuristic: if E > V * log₂(V) use the dense O(V²) method,
     * otherwise use the heap‑based O((V+E) log V) method.
     *
     * @param int $source Source vertex.
     * @return float[] Distance array.
     */
    public function shortestPaths(int $source): array
    {
        $V = $this->vertexCount;
        $E = $this->edgeCount();

        // Guard against log(0) and log(1) which are 0.
        $threshold = $V * max(1, (int)log($V, 2));

        if ($E > $threshold) {
            return $this->dijkstraArray($source);
        }
        return $this->dijkstraHeap($source);
    }
}
