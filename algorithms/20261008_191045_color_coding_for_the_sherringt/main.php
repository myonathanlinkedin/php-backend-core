<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

/**
 * Simple assertion wrapper that throws on failure.
 *
 * @param bool $condition
 * @param string $message
 */
function testAssert(bool $condition, string $message = ''): void
{
    if (!$condition) {
        $msg = $message !== '' ? $message : 'Assertion failed';
        throw new AssertionError($msg);
    }
}

/**
 * Helper to compare floating point estimates within relative tolerance.
 */
function assertApproxEqual(float $actual, float $expected, float $relTol = 0.15, string $msg = ''): void
{
    if ($expected == 0.0) {
        testAssert(abs($actual) < $relTol, $msg ?: "Expected ~0, got $actual");
        return;
    }
    $relError = abs($actual - $expected) / $expected;
    testAssert($relError <= $relTol, $msg ?: "Relative error $relError exceeds $relTol");
}

/* ---------- Unit Test Suite ---------- */

// Test 1: trivial graph with a single vertex, k = 1
$g1 = new Graph(1);
$cc1 = new ColorCoding($g1, 1, iterations: 200, seed: 42);
$est1 = $cc1->estimatePathCount();
assertApproxEqual($est1, 1.0, 0.01, 'Single vertex path count');

// Test 2: empty graph, any k > 0 should yield 0
$g2 = new Graph(0);
$cc2 = new ColorCoding($g2, 2, iterations: 100, seed: 7);
$est2 = $cc2->estimatePathCount();
testAssert($est2 === 0.0, 'Empty graph should have zero paths');

// Test 3: triangle graph, count simple paths of length 3 vertices (k = 3)
// In a complete graph K3, ordered simple paths of 3 distinct vertices = 3! = 6
$g3 = new Graph(3);
$g3->addEdge(0, 1);
$g3->addEdge(1, 2);
$g3->addEdge(0, 2);
$cc3 = new ColorCoding($g3, 3, iterations: 2000, seed: 123);
$est3 = $cc3->estimatePathCount();
assertApproxEqual($est3, 6.0, 0.10, 'Triangle 3‑vertex paths');

// Test 4: square (cycle of 4 vertices), k = 3
// Number of ordered simple paths of length 3 in C4 = 8
// Explanation: choose a start vertex (4 choices), then choose one of its two neighbours (2), then the next vertex (1) => 4*2*1 = 8
$g4 = new Graph(4);
$g4->addEdge(0, 1);
$g4->addEdge(1, 2);
$g4->addEdge(2, 3);
$g4->addEdge(3, 0);
$cc4 = new ColorCoding($g4, 3, iterations: 3000, seed: 999);
$est4 = $cc4->estimatePathCount();
assertApproxEqual($est4, 8.0, 0.12, 'Square 3‑vertex paths');

// Test 5: line graph of 5 vertices, k = 4
// Number of ordered simple paths of 4 vertices in a line of 5 = 2 * (5 - 4 + 1) * 3! = 2 * 2 * 6 = 24
// Reason: choose direction (2), choose starting position (2), then internal ordering is forced.
$g5 = new Graph(5);
$g5->addEdge(0, 1);
$g5->addEdge(1, 2);
$g5->addEdge(2, 3);
$g5->addEdge(3, 4);
$cc5 = new ColorCoding($g5, 4, iterations: 4000, seed: 2021);
$est5 = $cc5->estimatePathCount();
assertApproxEqual($est5, 24.0, 0.15, 'Line graph 4‑vertex paths');

// Test 6: k larger than number of vertices => 0
$g6 = new Graph(3);
$g6->addEdge(0, 1);
$g6->addEdge(1, 2);
$cc6 = new ColorCoding($g6, 5, iterations: 100, seed: 11);
$est6 = $cc6->estimatePathCount();
testAssert($est6 === 0.0, 'k > n should give zero paths');

echo "All tests passed.\n";
?>
