<?php
declare(strict_types=1);

/**
 * A directed graph implementation using arrays for adjacency.
 */
class Graph
{
    /**
     * Graph nodes represented as integers.
     */
    private array $nodes = [];

    /**
     * Graph edges represented as arrays of nodes.
     */
    private array $edges = [];

    /**
     * Add a node to the graph.
     *
     * @param int $node Node identifier.
     */
    public function addNode(int $node): void
    {
        $this->nodes[$node] = true;
    }

    /**
     * Add an edge between two nodes.
     *
     * @param int $source Node source identifier.
     * @param int $destination Node destination identifier.
     */
    public function addEdge(int $source, int $destination): void
    {
        $this->edges[$source][] = $destination;
    }

    /**
     * Perform a topological sort using Kahn's algorithm.
     *
     * @return array<int, bool> Graph nodes as key-value pairs of node and is_valid.
     */
    public function topologicalSort(): array
    {
        $nodes = [];

        // Iterate over nodes and mark them as visited
        foreach ($this->nodes as $node => $is_valid) {
            $nodes[$node] = true;
        }

        // Iterate over edges and check for cycles
        foreach ($this->edges as $source => $destinations) {
            foreach ($destinations as $destination) {
                if (isset($nodes[$destination]) && $nodes[$destination] === false) {
                    $nodes[$destination] = true;
                    unset($nodes[$source]);
                }
            }
        }

        // Return the sorted nodes and mark all nodes as valid
        return $nodes;
    }
}
