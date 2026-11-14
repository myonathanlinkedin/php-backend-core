<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Configure assert to throw exceptions for easier test failure handling.
ini_set('assert.exception', '1');
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 0);
assert_options(ASSERT_WARNING, 0);

/**
 * Simple test runner that executes a callable and reports success.
 *
 * @param string   $name Test name.
 * @param callable $fn   Test body.
 */
function runTest(string $name, callable $fn): void
{
    try {
        $fn();
        echo "[PASS] {$name}\n";
    } catch (Throwable $e) {
        echo "[FAIL] {$name}: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/* ---------- Unit Tests ---------- */

runTest('Construction with valid parameters', function (): void {
    $bf = new BloomFilter(1024, 4);
    assert($bf instanceof BloomFilter);
});

runTest('Construction with invalid size throws', function (): void {
    $thrown = false;
    try {
        new BloomFilter(0, 3);
    } catch (InvalidArgumentException $e) {
        $thrown = true;
    }
    assert($thrown, 'Expected exception for size=0');
});

runTest('Construction with invalid hash count throws', function (): void {
    $thrown = false;
    try {
        new BloomFilter(128, 0);
    } catch (InvalidArgumentException $e) {
        $thrown = true;
    }
    assert($thrown, 'Expected exception for hashCount=0');
});

runTest('Basic add/contains functionality', function (): void {
    $bf = new BloomFilter(256, 3);
    $items = ['apple', 'banana', 'cherry'];
    foreach ($items as $it) {
        $bf->add($it);
    }
    foreach ($items as $it) {
        assert($bf->contains($it), "Item {$it} should be reported present");
    }
    // An element not added should usually be absent (allowing false positives)
    $absent = 'durian';
    // Since false positives are possible, we only assert that the method does not crash.
    $bf->contains($absent);
});

runTest('Empty string handling', function (): void {
    $bf = new BloomFilter(128, 2);
    $bf->add('');
    assert($bf->contains(''), 'Empty string should be reported present after insertion');
});

runTest('False positive probability estimation', function (): void {
    $size = 1024;
    $k    = 5;
    $bf   = new BloomFilter($size, $k);
    $n    = 100;
    for ($i = 0; $i < $n; $i++) {
        $bf->add('item' . $i);
    }
    $prob = $bf->estimateFalsePositiveProbability($n);
    // The theoretical probability for these parameters is roughly 0.018.
    // Allow a generous tolerance due to integer rounding in the bit array.
    assert($prob > 0.0 && $prob < 0.1, "Probability {$prob} out of expected range");
});

runTest('Deterministic hashing produces same indices', function (): void {
    $bf = new BloomFilter(512, 4);
    $indicesFirst = iterator_to_array((function () use ($bf) {
        $ref = new ReflectionMethod($bf, 'hashIndices');
        $ref->setAccessible(true);
        return $ref->invoke($bf, 'consistent');
    })());
    $indicesSecond = iterator_to_array((function () use ($bf) {
        $ref = new ReflectionMethod($bf, 'hashIndices');
        $ref->setAccessible(true);
        return $ref->invoke($bf, 'consistent');
    })());
    assert($indicesFirst === $indicesSecond, 'Hash indices must be deterministic');
});

runTest('Large number of insertions does not overflow', function (): void {
    $bf = new BloomFilter(2048, 7);
    for ($i = 0; $i < 5000; $i++) {
        $bf->add('key_' . $i);
    }
    // Spot‑check a few inserted keys.
    assert($bf->contains('key_0'));
    assert($bf->contains('key_1234'));
    assert($bf->contains('key_4999'));
});

echo "All tests passed.\n";
