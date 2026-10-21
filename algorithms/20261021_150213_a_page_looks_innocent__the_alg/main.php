<?php

require_once 'core.php';

function assertEqual(array $a, array $b): void
{
    assert($a === $b);
}

// Test 1: Basic example
$breaker = new LineBreaker(10);
$words = [3, 2, 2, 5];
$lines = $breaker->breakLines($words);
assertEqual($lines, [[0, 1], [2, 3]]);

// Test 2: Single word exactly max width
$breaker = new LineBreaker(5);
$words = [5];
$lines = $breaker->breakLines($words);
assertEqual($lines, [[0, 0]]);

// Test 3: Single word overfull
$breaker = new LineBreaker(4);
$words = [6];
$lines = $breaker->breakLines($words);
assertEqual($lines, [[0, 0]]);

// Test 4: Empty paragraph
$breaker = new LineBreaker(10);
$words = [];
$lines = $breaker->breakLines($words);
assertEqual($lines, []);

// Test 5: Multiple lines with varying widths
$breaker = new LineBreaker(15);
$words = [4, 3, 2, 5, 6, 2, 3];
$lines = $breaker->breakLines($words);
// Expected optimal break: [0,2], [3,5], [6,6]
assertEqual($lines, [[0, 2], [3, 5], [6, 6]]);

// Test 6: All words fit in one line
$breaker = new LineBreaker(100);
$words = [10, 20, 30];
$lines = $breaker->breakLines($words);
assertEqual($lines, [[0, 2]]);

// Test 7: Large input performance check
$breaker = new LineBreaker(50);
$words = array_fill(0, 200, 1); // 200 words of width 1
$lines = $breaker->breakLines($words);
// Each line can contain up to 25 words + 24 spaces = 49 width
$expectedLines = [];
$pos = 0;
while ($pos < 200) {
    $end = min($pos + 25 - 1, 199);
    $expectedLines[] = [$pos, $end];
    $pos = $end + 1;
}
assertEqual($lines, $expectedLines);

// Test 8: Verify badness calculation for overfull line
$breaker = new LineBreaker(5);
$words = [3, 4]; // line width 3+1+4=8 >5
$lines = $breaker->breakLines($words);
assertEqual($lines, [[0, 1]]);

// All tests passed
echo "All tests passed.\n";
?>
