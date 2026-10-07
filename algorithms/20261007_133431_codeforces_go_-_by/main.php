<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

// Ensure assertions are active.
assert_options(ASSERT_ACTIVE,   1);
assert_options(ASSERT_WARNING, 1);
assert_options(ASSERT_BAIL,    0);
assert_options(ASSERT_CALLBACK, function (int $file, int $line, string $code, string $desc = null) {
    $msg = "Assertion failed at {$file}:{$line} - {$code}";
    if ($desc !== null) {
        $msg .= " : {$desc}";
    }
    throw new AssertionError($msg);
});

/**
 * Helper to run a test case and report success.
 *
 * @param string $name
 * @param callable $fn
 * @return void
 */
function runTest(string $name, callable $fn): void
{
    try {
        $fn();
        echo "[PASS] {$name}\n";
    } catch (Throwable $e) {
        echo "[FAIL] {$name} – " . $e->getMessage() . "\n";
    }
}

/* ---------- Test Suite ---------- */

runTest('Initial state: each element is its own set', function () {
    $uf = new UnionFind(5);
    for ($i = 0; $i < 5; $i++) {
        assert($uf->find($i) === $i, "Element {$i} should be its own root");
        assert($uf->connected($i, $i) === true, "Element {$i} must be connected to itself");
    }
});

runTest('Simple union and connectivity', function () {
    $uf = new UnionFind(4);
    $uf->union(0, 1);
    $uf->union(2, 3);
    assert($uf->connected(0, 1) === true);
    assert($uf->connected(2, 3) === true);
    assert($uf->connected(0, 2) === false);
    $uf->union(1, 2);
    assert($uf->connected(0, 3) === true);
});

runTest('Idempotent union (union same set twice)', function () {
    $uf = new UnionFind(3);
    $uf->union(0, 1);
    $rootBefore = $uf->find(0);
    $uf->union(0, 1); // second union should not change structure
    $rootAfter = $uf->find(0);
    assert($rootBefore === $rootAfter, 'Root should remain unchanged after redundant union');
});

runTest('Path compression reduces depth', function () {
    $uf = new UnionFind(6);
    // Create a linear chain: 0-1-2-3-4-5
    for ($i = 0; $i < 5; $i++) {
        $uf->union($i, $i + 1);
    }
    // Before any find, internal parent may be a chain.
    $parentsBefore = $uf->getParentArray();
    // Trigger path compression by finding the root of element 5.
    $root = $uf->find(5);
    assert($root === 0, 'Root of chain should be 0');
    $parentsAfter = $uf->getParentArray();
    // After compression, all elements should point directly to root.
    for ($i = 0; $i < 6; $i++) {
        assert($parentsAfter[$i] === 0, "Element {$i} should point directly to root after compression");
    }
    // Ensure that compression actually changed something.
    assert($parentsBefore !== $parentsAfter, 'Parent array should change after path compression');
});

runTest('Invalid element throws exception', function () {
    $uf = new UnionFind(2);
    $thrown = false;
    try {
        $uf->find(5);
    } catch (OutOfBoundsException $e) {
        $thrown = true;
    }
    assert($thrown === true, 'Accessing out‑of‑bounds element must throw');
});

runTest('Large random unions maintain connectivity invariants', function () {
    $size = 1000;
    $uf = new UnionFind($size);
    // Randomly union pairs.
    for ($i = 0; $i < $size * 5; $i++) {
        $a = random_int(0, $size - 1);
        $b = random_int(0, $size - 1);
        $uf->union($a, $b);
    }
    // Pick a few random pairs and verify that connectivity is symmetric.
    for ($i = 0; $i < 100; $i++) {
        $a = random_int(0, $size - 1);
        $b = random_int(0, $size - 1);
        $connectedAB = $uf->connected($a, $b);
        $connectedBA = $uf->connected($b, $a);
        assert($connectedAB === $connectedBA, "Connectivity must be symmetric for {$a},{$b}");
    }
});

echo "All tests completed.\n";
