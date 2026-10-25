<?php
declare(strict_types=1);

/**
 * Enum representing the operational status of a node in the AI gateway.
 */
enum NodeStatus: string {
    case UP   = 'up';
    case DOWN = 'down';
}

/**
 * Represents a logical processing node (e.g., an AI model instance).
 */
final class Node {
    public string $id;
    public NodeStatus $status;

    public function __construct(string $id, NodeStatus $status = NodeStatus::UP) {
        $this->id     = $id;
        $this->status = $status;
    }
}

/**
 * Directed edge with an associated routing cost.
 */
final class Edge {
    public string $from;
    public string $to;
    public int $cost;

    public function __construct(string $from, string $to, int $cost) {
        if ($cost < 0) {
            throw new InvalidArgumentException('Edge cost must be non‑negative.');
        }
        $this->from = $from;
        $this->to   = $to;
        $this->cost = $cost;
    }
}

/**
 * Result of a routing query: the selected path and its total cost.
 */
final class RouteResult {
    /** @var string[] Ordered list of node identifiers */
    public array $path;
    public int $totalCost;

    /**
     * @param string[] $path
     */
    public function __construct(array $path, int $totalCost) {
        $this->path      = $path;
        $this->totalCost = $totalCost;
    }
}
