<?php
declare(strict_types=1);
require_once 'types.php';

/**
 * Floyd‑Warshall algorithm with path reconstruction.
 *
 * @param Graph $g
 * @return array{dist: array<int, array<int, float>>, next: array<int, array<int, ?int>>}
 */
function floydWarshall(Graph $g): array
{
    $n = $g->getNodeCount();
    $dist = $g->getAdjacency();
    $next = [];

    // initialise next matrix
    for ($i = 0; $i < $n; $i++) {
        $next[$i] = array_fill(0, $n, null);
        for ($j = 0; $j < $n; $j++) {
            if ($i !== $j && $dist[$i][$j] < INF) {
                $next[$i][$j] = $j;
            }
        }
    }

    for ($k = 0; $k < $n; $k++) {
        for ($i = 0; $i < $n; $i++) {
            if ($dist[$i][$k] === INF) {
                continue;
            }
            for ($j = 0; $j < $n; $j++) {
                if ($dist[$k][$j] === INF) {
                    continue;
                }
                $newDist = $dist[$i][$k] + $dist[$k][$j];
                if ($newDist < $dist[$i][$j]) {
                    $dist[$i][$j] = $newDist;
                    $next[$i][$j] = $next[$i][$k];
                }
            }
        }
    }

    return ['dist' => $dist, 'next' => $next];
}

/**
 * Reconstruct the shortest path from $src to $dst using the $next matrix.
 *
 * @param array<int, array<int, ?int>> $next
 * @param int $src
 * @param int $dst
 * @return array<int> list of node ids (including src and dst). Empty if unreachable.
 */
function reconstructPath(array $next, int $src, int $dst): array
{
    if ($next[$src][$dst] === null) {
        return [];
    }
    $path = [$src];
    while ($src !== $dst) {
        $src = $next[$src][$dst];
        if ($src === null) {
            return []; // should not happen
        }
        $path[] = $src;
    }
    return $path;
}

/**
 * Compute the minimum cost from $src to $dst using at most $maxSegments segment
 * routing hops, where each segment is a shortest‑path sub‑segment.
 *
 * @param Graph $g
 * @param int $src
 * @param int $dst
 * @param int $maxSegments
 * @return array{cost: float, segments: array<int>} cost = INF if unreachable.
 */
function computeMinCostWithSegments(Graph $g, int $src, int $dst, int $maxSegments): array
{
    $n = $g->getNodeCount();
    $fw = floydWarshall($g);
    $dist = $fw['dist'];
    $next = $fw['next'];

    // DP table: dp[segments][node] = min cost to reach node using exactly 'segments' segments
    $dp = array_fill(0, $maxSegments + 1, array_fill(0, $n, INF));
    $prevNode = array_fill(0, $maxSegments + 1, array_fill(0, $n, null));

    $dp[0][$src] = 0.0;

    for ($seg = 1; $seg <= $maxSegments; $seg++) {
        for ($u = 0; $u < $n; $u++) {
            if ($dp[$seg - 1][$u] === INF) {
                continue;
            }
            for ($v = 0; $v < $n; $v++) {
                if ($dist[$u][$v] === INF) {
                    continue;
                }
                $newCost = $dp[$seg - 1][$u] + $dist[$u][$v];
                if ($newCost < $dp[$seg][$v]) {
                    $dp[$seg][$v] = $newCost;
                    $prevNode[$seg][$v] = $u;
                }
            }
        }
    }

    // Find best cost among 1..maxSegments (0 segments only valid if src==dst)
    $bestCost = INF;
    $bestSegCount = null;
    for ($seg = 0; $seg <= $maxSegments; $seg++) {
        if ($dp[$seg][$dst] < $bestCost) {
            $bestCost = $dp[$seg][$dst];
            $bestSegCount = $seg;
        }
    }

    if ($bestCost === INF) {
        return ['cost' => INF, 'segments' => []];
    }

    // Reconstruct segment list (list of waypoints including src and dst)
    $segments = [];
    $current = $dst;
    $seg = $bestSegCount;
    while ($seg > 0) {
        $prev = $prevNode[$seg][$current];
        if ($prev === null) {
            // Should not happen
            break;
        }
        $segments[] = $current;
        $current = $prev;
        $seg--;
    }
    $segments[] = $src;
    $segments = array_reverse($segments);

    // Expand each segment into the underlying shortest path and concatenate
    $fullPath = [];
    for ($i = 0; $i < count($segments) - 1; $i++) {
        $subPath = reconstructPath($next, $segments[$i], $segments[$i + 1]);
        // avoid duplicating the connecting node
        if ($i > 0 && count($subPath) > 0) {
            array_shift($subPath);
        }
        $fullPath = array_merge($fullPath, $subPath);
    }

    return ['cost' => $bestCost, 'segments' => $segments, 'fullPath' => $fullPath];
}
