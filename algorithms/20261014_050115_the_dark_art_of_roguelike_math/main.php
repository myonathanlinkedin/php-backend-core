<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

/* ---------- HyperbolicCurve Unit Tests ---------- */
$curve = new HyperbolicCurve(2.0, 3.0);

// Test getY with valid x
$y = $curve->getY(4.0);
assert(abs($y - (3.0 * sqrt((4.0/2.0)*(4.0/2.0) - 1.0))) < 1e-9);

// Test getX with valid y
$x = $curve->getX(3.0);
assert(abs($x - (2.0 * sqrt((3.0/3.0)*(3.0/3.0) + 1.0))) < 1e-9);

// Test parametric point
[$xp, $yp] = $curve->getPoint(0.0);
assert(abs($xp - 2.0) < 1e-9);
assert(abs($yp - 0.0) < 1e-9);

// Edge case: x exactly a
$yAtA = $curve->getY(2.0);
assert(abs($yAtA - 0.0) < 1e-9);

// Exception on invalid x (|x| < a)
$exceptionCaught = false;
try {
    $curve->getY(1.5);
} catch (InvalidArgumentException $e) {
    $exceptionCaught = true;
}
assert($exceptionCaught === true);

/* ---------- DAG Unit Tests ---------- */
$dag = new DAG();

// Node 'a' returns constant 5
$dag->addNode('a', static fn() => 5);

// Node 'b' depends on 'a' and returns a * 2
$dag->addNode('b', static fn(int $a) => $a * 2, ['a']);

// Node 'c' depends on 'a' and 'b' and returns a + b
$dag->addNode('c', static fn(int $a, int $b) => $a + $b, ['a', 'b']);

// Evaluate 'c' should be 5 + 10 = 15
$resultC = $dag->evaluate('c');
assert($resultC === 15);

// Evaluate all reachable from 'c'
$all = $dag->evaluateAll('c');
assert($all['a'] === 5);
assert($all['b'] === 10);
assert($all['c'] === 15);

// Test missing node exception
$missingCaught = false;
try {
    $dag->evaluate('nonexistent');
} catch (DAGException $e) {
    $missingCaught = true;
}
assert($missingCaught === true);

// Test cycle detection
$dagCycle = new DAG();
$dagCycle->addNode('x', static fn($y) => $y + 1, ['y']);
$dagCycle->addNode('y', static fn($x) => $x + 1, ['x']);
$cycleCaught = false;
try {
    $dagCycle->evaluate('x');
} catch (DAGException $e) {
    $cycleCaught = true;
}
assert($cycleCaught === true);

/* ---------- Simple Benchmark (optional) ---------- */
function benchmarkDAG(int $size): float
{
    $dag = new DAG();
    // Linear chain a0 -> a1 -> ... -> aN
    $dag->addNode('a0', static fn() => 1);
    for ($i = 1; $i <= $size; $i++) {
        $prev = 'a' . ($i - 1);
        $curr = 'a' . $i;
        $dag->addNode($curr, static fn(int $v) => $v + 1, [$prev]);
    }
    $start = microtime(true);
    $dag->evaluate('a' . $size);
    return microtime(true) - $start;
}

// Run a tiny benchmark to ensure code path works (no assertion needed)
benchmarkDAG(1000);

// End of tests – if no assertion failed, implementation is correct.
