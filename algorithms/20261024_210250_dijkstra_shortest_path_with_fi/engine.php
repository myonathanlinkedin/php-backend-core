<?php
declare(strict_types=1);

require_once 'types.php';

final class Dijkstra
{
    /**
     * Computes shortest path distances from source to all vertices in the graph.
     *
     * @param GraphInterface $graph
     * @param int $source
     * @return array<int, int> Mapping from vertex to shortest distance.
     */
    public static function shortestPath(GraphInterface $graph, int $source): array
    {
        $vertices = $graph->getVertices();
        $dist = array_fill_keys($vertices, PHP_INT_MAX);
        $dist[$source] = 0;

        $heap = new FibonacciHeap();
        $nodeMap = [];

        foreach ($vertices as $v) {
            $node = $heap->insert($dist[$v], $v);
            $nodeMap[$v] = $node;
        }

        while (!$heap->isEmpty()) {
            $minNode = $heap->extractMin();
            if ($minNode === null) {
                break;
            }
            $u = $minNode->value;
            $du = $minNode->key;

            foreach ($graph->getEdgesFrom($u) as $edge) {
                $v = $edge->to;
                $alt = $du + $edge->weight;
                if ($alt < $dist[$v]) {
                    $dist[$v] = $alt;
                    $heap->decreaseKey($nodeMap[$v], $alt);
                }
            }
        }

        return $dist;
    }
}
