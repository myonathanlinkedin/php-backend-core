<?php
declare(strict_types=1);
require_once 'types.php';
require_once 'engine.php';

// Helper to compare floating point numbers safely
function floatEquals(float $a, float $b, float $epsilon = 1e-9): bool
{
    return abs($a - $b) < $epsilon;
}

// ---------- Unit Tests ----------
function runTests(): void
{
    // Test 1: Simple triangle graph
    // 0 --1--> 1 --1--> 2
    // 0 --5--> 2 (direct)
    $g = new Graph(3);
    $g->addEdge(new Edge(0, 1, 1.0));
    $g->addEdge(new Edge(1, 2, 1.0));
    $g->addEdge(new Edge(0, 2, 5.0));

    // 1 segment (direct SR) should pick the direct edge cost 5
    $res1 = computeMinCostWithSegments($g, 0, 2, 1);
    assert(floatEquals($res1['cost'], 5.0), 'Test1: cost with 1 segment');
    assert($res1['segments'] === [0, 2], 'Test1: segment list with 1 segment');

    // 2 segments allow using 0->1 and 1->2, total cost 2
    $res2 = computeMinCostWithSegments($g, 0, 2, 2);
    assert(floatEquals($res2['cost'], 2.0), 'Test2: cost with 2 segments');
    assert($res2['segments'] === [0, 1, 2], 'Test2: segment list with 2 segments');
    assert($res2['fullPath'] === [0, 1, 2], 'Test2: full path expansion');

    // Test 2: Unreachable destination
    $g2 = new Graph(2);
    $g2->addEdge(new Edge(0, 0, 0.0)); // self loop only
    $res3 = computeMinCostWithSegments($g2, 0, 1, 3);
    assert($res3['cost'] === INF, 'Test3: unreachable cost is INF');
    assert($res3['segments'] === [], 'Test3: unreachable segments empty');

    // Test 3: Zero‑segment case (src == dst)
    $g3 = new Graph(1);
    $res4 = computeMinCostWithSegments($g3, 0, 0, 0);
    assert(floatEquals($res4['cost'], 0.0), 'Test4: zero segment cost zero');
    assert($res4['segments'] === [0], 'Test4: zero segment list contains only src');

    // Test 4: Larger graph with multiple equal‑cost paths
    // 0->1 (1), 1->3 (1), 0->2 (1), 2->3 (1), 0->3 (3)
    $g4 = new Graph(4);
    $g4->addEdge(new Edge(0, 1, 1.0));
    $g4->addEdge(new Edge(1, 3, 1.0));
    $g4->addEdge(new Edge(0, 2, 1.0));
    $g4->addEdge(new Edge(2, 3, 1.0));
    $g4->addEdge(new Edge(0, 3, 3.0));

    // With 1 segment, direct edge cost 3
    $res5 = computeMinCostWithSegments($g4, 0, 3, 1);
    assert(floatEquals($res5['cost'], 3.0), 'Test5: cost with 1 segment');
    assert($res5['segments'] === [0, 3], 'Test5: segment list with 1 segment');

    // With 2 segments, cheapest is via node 1 or 2, cost 2
    $res6 = computeMinCostWithSegments($g4, 0, 3, 2);
    assert(floatEquals($res6['cost'], 2.0), 'Test6: cost with 2 segments');
    // segment list could be [0,1,3] or [0,2,3]; accept either
    $validSegments = [
        [0, 1, 3],
        [0, 2, 3],
    ];
    assert(in_array($res6['segments'], $validSegments, true), 'Test6: segment list with 2 segments');

    // With 3 segments, still cost 2 (no improvement)
    $res7 = computeMinCostWithSegments($g4, 0, 3, 3);
    assert(floatEquals($res7['cost'], 2.0), 'Test7: cost with 3 segments');

    echo "All unit tests passed.\n";
}

// ---------- Demo ----------
function demo(): void
{
    echo "=== Segment Routing Cost Minimisation Demo ===\n";

    // Build a sample network (5 nodes)
    $g = new Graph(5);
    $edges = [
        [0, 1, 2.0],
        [1, 2, 2.0],
        [2, 3, 2.0],
        [3, 4, 2.0],
        [0, 2, 5.0],
        [1, 3, 5.0],
        [0, 4, 15.0],
    ];
    foreach ($edges as [$u, $v, $w]) {
        $g->addEdge(new Edge($u, $v, $w));
    }

    $src = 0;
    $dst = 4;
    $maxSeg = 3;

    $result = computeMinCostWithSegments($g, $src, $dst, $maxSeg);
    echo "Source: $src, Destination: $dst, Max Segments: $maxSeg\n";
    echo "Minimum Cost: " . $result['cost'] . "\n";
    echo "Segment Waypoints: " . implode(' -> ', $result['segments']) . "\n";
    echo "Expanded Path: " . implode(' -> ', $result['fullPath']) . "\n";
}

// Execute tests and demo
runTests();
demo();
