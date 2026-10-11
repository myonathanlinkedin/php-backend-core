<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

/**
 * Simple assertion wrapper that throws on failure.
 *
 * @param bool $condition
 * @param string $message
 * @throws AssertionError
 */
function expect(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new AssertionError($message ?: 'Assertion failed');
    }
}

/* ---------- Unit Tests ---------- */

// Test 1: Construction with zero elements
$uf0 = new UnionFind(0);
expect($uf0->getComponentCount() === 0, 'Zero‑size UF should have 0 components');

// Test 2: Single element – find returns itself, component count stays 1
$uf1 = new UnionFind(1);
expect($uf1->find(0) === 0, 'Single element should be its own root');
expect($uf1->getComponentCount() === 1, 'Single element UF should have 1 component');
expect($uf1->connected(0, 0), 'Element must be connected to itself');

// Test 3: Basic union and connectivity
$uf = new UnionFind(5);
expect($uf->getComponentCount() === 5, 'Initial component count mismatch');

$uf->union(0, 1);
expect($uf->connected(0, 1), '0 and 1 should be connected after union');
expect($uf->getComponentCount() === 4, 'Component count should decrease after union');

$uf->union(1, 2);
expect($uf->connected(0, 2), 'Transitive connectivity failed');
expect($uf->getComponentCount() === 3, 'Component count after second union');

$uf->union(3, 4);
expect($uf->connected(3, 4), '3 and 4 should be connected');
expect($uf->getComponentCount() === 2, 'Component count after third union');

$uf->union(2, 4);
expect($uf->connected(0, 4), 'All elements should now be in one component');
expect($uf->getComponentCount() === 1, 'Final component count should be 1');

// Test 4: Idempotent union (union same set multiple times)
$uf->union(0, 4); // already connected
expect($uf->getComponentCount() === 1, 'Component count must stay unchanged on redundant union');

// Test 5: Out‑of‑bounds handling
$exceptionCaught = false;
try {
    $uf->find(5);
} catch (OutOfBoundsException $e) {
    $exceptionCaught = true;
}
expect($exceptionCaught, 'find() should throw OutOfBoundsException for invalid index');

$exceptionCaught = false;
try {
    $uf->union(-1, 0);
} catch (OutOfBoundsException $e) {
    $exceptionCaught = true;
}
expect($exceptionCaught, 'union() should throw OutOfBoundsException for negative index');

// Test 6: Randomized stress test against a naive model
function naiveUnionFind(int $size): array
{
    // Returns an array where $parent[i] is the representative (simple linear scan)
    $parent = range(0, $size - 1);
    return $parent;
}
function naiveFind(array &$parent, int $x): int
{
    while ($parent[$x] !== $x) {
        $x = $parent[$x];
    }
    return $x;
}
function naiveUnion(array &$parent, int $a, int $b): void
{
    $ra = naiveFind($parent, $a);
    $rb = naiveFind($parent, $b);
    if ($ra === $rb) {
        return;
    }
    // Merge by making ra point to rb (no rank heuristic)
    $parent[$ra] = $rb;
}
function naiveConnected(array &$parent, int $a, int $b): bool
{
    return naiveFind($parent, $a) === naiveFind($parent, $b);
}

// Parameters for stress test
$N = 100;
$operations = 500;
$ufStress = new UnionFind($N);
$naive = naiveUnionFind($N);
for ($i = 0; $i < $operations; $i++) {
    $a = random_int(0, $N - 1);
    $b = random_int(0, $N - 1);
    $op = random_int(0, 2); // 0=find,1=union,2=connected

    if ($op === 0) {
        // find – just ensure both implementations agree on root identity
        $rootUF = $ufStress->find($a);
        $rootNaive = naiveFind($naive, $a);
        expect($rootUF === $rootNaive, "Find mismatch at iteration {$i}");
    } elseif ($op === 1) {
        $ufStress->union($a, $b);
        naiveUnion($naive, $a, $b);
        expect($ufStress->connected($a, $b) === naiveConnected($naive, $a, $b), "Union/connected mismatch at iteration {$i}");
    } else {
        $connectedUF = $ufStress->connected($a, $b);
        $connectedNaive = naiveConnected($naive, $a, $b);
        expect($connectedUF === $connectedNaive, "Connected mismatch at iteration {$i}");
    }
}

// If we reach this point, all assertions passed.
echo "All Orleans Union‑Find tests passed successfully.\n";
