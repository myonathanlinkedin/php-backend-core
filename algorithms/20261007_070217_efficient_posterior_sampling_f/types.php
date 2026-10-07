<?php
declare(strict_types=1);

class Edge
{
    public int $u;
    public int $v;
    /** @var int $y Measurement (+1 or -1) */
    public int $y;

    public function __construct(int $u, int $v, int $y)
    {
        $this->u = $u;
        $this->v = $v;
        $this->y = $y;
    }
}

class Graph
{
    public int $n;
    /** @var array<int, array<int, Edge>> $adj adjacency list keyed by node */
    public array $adj = [];

    public function __construct(int $n)
    {
        $this->n = $n;
        for ($i = 0; $i < $n; $i++) {
            $this->adj[$i] = [];
        }
    }

    public function addEdge(int $u, int $v, int $y): void
    {
        $edge = new Edge($u, $v, $y);
        $this->adj[$u][$v] = $edge;
        $this->adj[$v][$u] = $edge; // undirected
    }

    /**
     * @return array<int, Edge>
     */
    public function neighbors(int $node): array
    {
        return $this->adj[$node];
    }
}
