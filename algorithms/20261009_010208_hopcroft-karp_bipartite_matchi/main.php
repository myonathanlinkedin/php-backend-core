<?php
declare(strict_types=1);
require_once __DIR__ . '/core.php';

/**
 * Helper to assert expected matching size.
 *
 * @param int   $expectedSize
 * @param array $adj          adjacency list for left side
 * @param int   $nLeft
 * @param int   $nRight
 */
function assertMatching(int $expectedSize, array $adj, int $nLeft, int $nRight): void
{
    $hk = new HopcroftKarp($adj, $nLeft, $nRight);
    $size = $hk->maxMatching();
    assert($size === $expectedSize, "Expected matching size $expectedSize, got $size");
}

/* Test 1: empty graph */
assertMatching(0, [], 0, 0);

/* Test 2: single edge */
$adj2 = [0 => [0]];
assertMatching(1, $adj2, 1, 1);

/* Test 3: known graph with perfect matching of size 3 */
$adj3 = [
    0 => [0, 1],
    1 => [0, 2],
    2 => [1],
];
assertMatching(3, $adj3, 3, 3);

/* Test 4: graph without perfect matching */
$adj4 = [
    0 => [0],
    1 => [0],
    2 => [0],
];
assertMatching(1, $adj4, 3, 1);

/* Test 5: larger deterministic graph, maximum matching size 6 */
$adj5 = [
    0 => [0, 1, 2],
    1 => [0, 3],
    2 => [1, 3, 4],
    3 => [2, 4],
    4 => [2, 3, 5],
    5 => [5],
];
assertMatching(6, $adj5, 6, 6);

echo "All Hopcroft-Karp tests passed.\n";
