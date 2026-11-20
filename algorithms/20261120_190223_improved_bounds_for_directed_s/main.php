<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Enable strict assertions.
ini_set('assert.exception', '1');
ini_set('assert.active', '1');

/**
 * Helper to compare two float arrays with tolerance.
 */
function assertFloatArraysEqual(array $expected, array $actual, float $eps = 1e-9): void
{
    $len = count($expected);
    if ($len !== count($actual)) {
        throw new AssertionError('Array lengths differ');
    }
    for ($i = 0; $i < $len; ++$i) {
        $e = $expected[$i];
        $a = $actual[$i];
        if (is_infinite($e) && is_infinite($a)) {
            continue;
        }
        if (abs($e - $a) > $eps) {
            throw new AssertionError("Mismatch at index $i: expected $e, got $a");
        }
    }
}

/**
 * Test 1 – Simple triangle graph.
 */
function testTriangle(): void
{
    $g = new Graph(3);
    $g->addEdge(0, 1, 1.0);
    $g->addEdge(1, 2, 2.0);
    $g->addEdge(0, 2, 4.0);

    $dist = $g->shortestPaths(0);
    $expected = [0.0, 1.0, 3.0];
    assertFloatArraysEqual($expected, $dist);
}

/**
 * Test 2 – Disconnected vertex.
 */
function testDisconnected(): void
{
    $g = new Graph(4);
    $g->addEdge(0, 1, 5.0);
    $g->addEdge(1, 2, 3.0);
    // Vertex 3 isolated.

    $dist = $g->shortestPaths(0);
    $expected = [0.0, 5.0, 8.0, INF];
    assertFloatArraysEqual($expected, $dist);
}

/**
 * Test 3 – Dense graph (complete digraph) to trigger array‑based algorithm.
 */
function testDense(): void
{
    $V = 6;
    $g = new Graph($V);
    // Complete directed graph with weight = 1 for all edges except self‑loops.
    for ($i = 0; $i < $V; ++$i) {
        for ($j = 0; $j < $V; ++$j) {
            if ($i !== $j) {
                $g->addEdge($i, $j, 1.0);
            }
        }
    }

    $dist = $g->shortestPaths(0);
    $expected = array_fill(0, $V, 1.0);
    $expected[0] = 0.0;
    assertFloatArraysEqual($expected, $dist);
}

/**
 * Test 4 – Large sparse random graph (stress test for heap path).
 */
function testSparseRandom(): void
{
    $V = 200;
    $E = 400; // Sparse: roughly 2 edges per vertex.
    $g = new Graph($V);
    $rng = mt_rand();

    // Ensure connectivity by creating a chain.
    for ($i = 0; $i < $V - 1; ++$i) {
        $g->addEdge($i, $i + 1, 1.0);
    }

    // Add random extra edges.
    $added = 0;
    while ($added < $E) {
        $u = random_int(0, $V - 1);
        $v = random_int(0, $V - 1);
        if ($u !== $v) {
            $g->addEdge($u, $v, random_int(1, 10));
            ++$added;
        }
    }

    $dist = $g->shortestPaths(0);
    // In the chain, distance to vertex i is i (since all chain edges weight 1).
    for ($i = 0; $i < $V; ++$i) {
        assert($dist[$i] <= $i + 10 * $E, 'Distance sanity check failed');
    }
}

/**
 * Execute all tests.
 */
function runAllTests(): void
{
    testTriangle();
    testDisconnected();
    testDense();
    testSparseRandom();
    echo "All tests passed.\n";
}

runAllTests();
