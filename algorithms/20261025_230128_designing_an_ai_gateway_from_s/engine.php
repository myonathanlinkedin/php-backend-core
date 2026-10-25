<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Core routing engine implementing Dijkstra's algorithm with failover support.
 */
final class Graph {
    /** @var array<string, Node> */
    private array $nodes = [];

    /** @var array<string, Edge[]> adjacency list keyed by source node id */
    private array $adjacency = [];

    /**
     * Adds a node to the graph. Overwrites any existing node with the same id.
     */
    public function addNode(Node $node): void {
        $this->nodes[$node->id] = $node;
        // Ensure adjacency entry exists
        $this->adjacency[$node->id] ??= [];
    }

    /**
     * Adds a directed edge. Both source and destination nodes must exist.
     */
    public function addEdge(Edge $edge): void {
        if (!isset($this->nodes[$edge->from]) || !isset($this->nodes[$edge->to])) {
            throw new InvalidArgumentException('Both endpoints of an edge must be added as nodes before the edge.');
        }
        $this->adjacency[$edge->from][] = $edge;
    }

    /**
     * Updates the operational status of a node.
     */
    public function setNodeStatus(string $nodeId, NodeStatus $status): void {
        if (!isset($this->nodes[$nodeId])) {
            throw new InvalidArgumentException("Node '{$nodeId}' does not exist.");
        }
        $this->nodes[$nodeId]->status = $status;
    }

    /**
     * Computes the lowest‑cost path from $source to $target, ignoring nodes marked DOWN.
     *
     * @return RouteResult|null Null if no viable path exists.
     */
    public function getShortestPath(string $source, string $target): ?RouteResult {
        // Validate source and target availability
        if (!isset($this->nodes[$source]) || $this->nodes[$source]->status !== NodeStatus::UP) {
            return null;
        }
        if (!isset($this->nodes[$target]) || $this->nodes[$target]->status !== NodeStatus::UP) {
            return null;
        }

        // Initialization
        $dist = [];
        $prev = [];
        foreach ($this->nodes as $id => $_) {
            $dist[$id] = PHP_INT_MAX;
            $prev[$id] = null;
        }
        $dist[$source] = 0;

        // SplPriorityQueue extracts highest priority, so we store negative distances.
        $queue = new SplPriorityQueue();
        $queue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
        $queue->insert($source, 0); // priority = -0

        while (!$queue->isEmpty()) {
            $extracted = $queue->extract();
            $u         = $extracted['data'];
            $priority  = $extracted['priority'];
            $currentDist = -$priority; // revert sign

            // Skip stale entries
            if ($currentDist > $dist[$u]) {
                continue;
            }

            if ($u === $target) {
                break; // optimal path found
            }

            foreach ($this->adjacency[$u] ?? [] as $edge) {
                $v = $edge->to;
                // Skip nodes that are down
                if ($this->nodes[$v]->status !== NodeStatus::UP) {
                    continue;
                }
                $alt = $dist[$u] + $edge->cost;
                if ($alt < $dist[$v]) {
                    $dist[$v] = $alt;
                    $prev[$v] = $u;
                    $queue->insert($v, -$alt);
                }
            }
        }

        if ($dist[$target] === PHP_INT_MAX) {
            return null; // unreachable
        }

        // Reconstruct path
        $path = [];
        $u    = $target;
        while ($u !== null) {
            array_unshift($path, $u);
            $u = $prev[$u];
        }

        return new RouteResult($path, $dist[$target]);
    }
}
