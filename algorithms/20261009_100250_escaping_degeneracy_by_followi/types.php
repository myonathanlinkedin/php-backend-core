<?php
declare(strict_types=1);

/**
 * Represents a directed edge in the network flow graph.
 * @immutable
 */
final class Edge
{
    public function __construct(
        public readonly int $u,
        public readonly int $v,
        public readonly int $capacity
    ) {}
}

/**
 * Represents a node in the network flow graph.
 * @immutable
 */
final class Node
{
    public function __construct(
        public readonly int $id,
        public readonly int $supply
    ) {}
}

/**
 * Represents a directed graph for network flow problems.
 * @immutable
 */
final class Graph
{
    /**
     * @param Node[] $nodes
     * @param Edge[] $edges
     */
    public function __construct(
        public readonly array $nodes,
        public readonly array $edges
    ) {}
}

/**
 * Represents the result of a max-flow computation.
 * @immutable
 */
final class FlowResult
{
    /**
     * @param array<int, array<int, int>> $flows
     */
    public function __construct(
        public readonly int $totalFlow,
        public readonly array $flows
    ) {}
}
