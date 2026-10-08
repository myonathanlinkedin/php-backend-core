<?php

declare(strict_types=1);
require_once 'core.php';

$solver = new SubsetSumSolver();

/* Test 1: Simple positive numbers */
assert($solver->hasSubsetSum([1, 2, 3, 4, 5], 9) === true, 'Test 1 failed');

/* Test 2: No subset sums to target */
assert($solver->hasSubsetSum([1, 2, 3], 7) === false, 'Test 2 failed');

/* Test 3: Target zero with non-empty set */
assert($solver->hasSubsetSum([5, -5, 10], 0) === true, 'Test 3 failed');

/* Test 4: Empty array, target zero */
assert($solver->hasSubsetSum([], 0) === true, 'Test 4 failed');

/* Test 5: Empty array, non-zero target */
assert($solver->hasSubsetSum([], 5) === false, 'Test 5 failed');

/* Test 6: Negative numbers */
assert($solver->hasSubsetSum([-1, -2, -3, 6], 0) === true, 'Test 6 failed');

/* Test 7: Large set with multiple solutions */
$largeSet = range(1, 20); // 1..20
assert($solver->hasSubsetSum($largeSet, 210) === true, 'Test 7 failed'); // sum of all

/* Test 8: Large set, impossible target */
assert($solver->hasSubsetSum($largeSet, 211) === false, 'Test 8 failed');

/* Test 9: Duplicate numbers */
assert($solver->hasSubsetSum([5, 5, 5], 10) === true, 'Test 9 failed');

/* Test 10: Subset sum with single element */
assert($solver->hasSubsetSum([7], 7) === true, 'Test 10 failed');
assert($solver->hasSubsetSum([7], 5) === false, 'Test 10b failed');

/* Benchmark: measure execution time for 40 elements */
$benchmarkSet = [];
for ($i = 1; $i <= 40; $i++) {
    $benchmarkSet[] = $i;
}
$start = microtime(true);
$found = $solver->hasSubsetSum($benchmarkSet, 820); // sum of 1..40
$duration = microtime(true) - $start;
assert($found === true, 'Benchmark test failed');
assert($duration < 0.5, 'Benchmark too slow');

/* All tests passed */
echo "All tests passed.\n";
?>
