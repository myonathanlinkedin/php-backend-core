<?php
declare(strict_types=1);

class Edge
{
    public int $from;
    public int $to;
    public float $weight;

    public function __construct(int $from, int $to, float $weight)
    {
        $this->from = $from;
        $this->to = $to;
        $this->weight = $weight;
    }
}

class Graph
{
    /** @var array<int, array<int, float>> adjacency matrix */
    private array $adj = [];
    private int $nodeCount;

    public function __construct(int $nodeCount)
    {
        $this->nodeCount = $nodeCount;
        // initialise adjacency with INF (unreachable)
        for ($i = 0; $i < $nodeCount; $i++) {
            $this->adj[$i] = array_fill(0, $nodeCount, INF);
            $this->adj[$i][$i] = 0.0;
        }
    }

    public function addEdge(Edge $e): void
    {
        // keep the smallest weight if multiple edges exist
        if ($e->weight < $this->adj[$e->from][$e->to]) {
            $this->adj[$e->from][$e->to] = $e->weight;
        }
    }

    /** @return int */
    public function getNodeCount(): int
    {
        return $this->nodeCount;
    }

    /** @return array<int, array<int, float>> */
    public function getAdjacency(): array
    {
        return $this->adj;
    }
}

class Demand
{
    public int $src;
    public int $dst;
    public float $volume;

    public function __construct(int $src, int $dst, float $volume)
    {
        $this->src = $src;
        $this->dst = $dst;
        $this->volume = $volume;
    }
}
