<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

// Helper to assert equality with a message.
function assertEqual(mixed $expected, mixed $actual, string $msg = ''): void
{
    assert($expected === $actual, $msg ?: "Expected " . var_export($expected, true) . " but got " . var_export($actual, true));
}

// Helper to assert that a value is greater than or equal to another.
function assertGreaterOrEqual(int $min, int $actual, string $msg = ''): void
{
    assert($actual >= $min, $msg ?: "Expected $actual >= $min");
}

// Helper to assert that a value is less than or equal to another.
function assertLessOrEqual(int $max, int $actual, string $msg = ''): void
{
    assert($actual <= $max, $msg ?: "Expected $actual <= $max");
}

// --------------------
// Test 1: Basic counting
// --------------------
$width = 1000;
$depth = 5;
$cms   = new CountMinSketch($width, $depth);

// Insert known frequencies.
$freqs = [
    'apple'  => 120,
    'banana' => 80,
    'cherry' => 5,
    'date'   => 1,
];

foreach ($freqs as $item => $count) {
    $cms->addWithTracking($item, $count);
}

// Verify total.
assertEqual(array_sum($freqs), $cms->total(), 'Total count mismatch');

// Verify each estimate is at least the true count (no under‑estimation).
foreach ($freqs as $item => $trueCount) {
    $est = $cms->estimate($item);
    assertGreaterOrEqual($trueCount, $est, "Estimate for $item underestimates");
}

// Verify error bound: with width w, error ≤ total / w with probability 1 - 1/e^depth.
// For deterministic test we just ensure estimate is not absurdly high.
$maxError = (int)ceil($cms->total() / $width);
foreach ($freqs as $item => $trueCount) {
    $est = $cms->estimate($item);
    assertLessOrEqual($trueCount + $maxError, $est, "Estimate for $item exceeds error bound");
}

// --------------------
// Test 2: Heavy hitters detection
// --------------------
$phi = 0.10; // 10% of total
$heavy = $cms->getHeavyHitters($phi);

// Expected heavy hitters: apple (120) and banana (80) because total = 206, 10% = 20.6
assertEqual(2, count($heavy), 'Heavy hitter count mismatch');
assertTrue(isset($heavy['apple']), 'Apple should be a heavy hitter');
assertTrue(isset($heavy['banana']), 'Banana should be a heavy hitter');
assertFalse(isset($heavy['cherry']), 'Cherry should not be a heavy hitter');

// --------------------
// Test 3: Edge cases
// --------------------
$emptySketch = new CountMinSketch(10, 3);
assertEqual(0, $emptySketch->total(), 'Empty sketch total should be 0');
assertEqual(0, $emptySketch->estimate('nothing'), 'Estimate on empty sketch should be 0');
assertEqual([], $emptySketch->getHeavyHitters(0.5), 'Heavy hitters on empty sketch should be empty');

// --------------------
// Test 4: Incremental adds and zero increment handling
// --------------------
$incSketch = new CountMinSketch(50, 4);
$incSketch->addWithTracking('x', 0); // should be a no‑op
assertEqual(0, $incSketch->total(), 'Zero increment should not affect total');
$incSketch->addWithTracking('x', 3);
assertEqual(3, $incSketch->total(), 'Total after adding 3 should be 3');
assertEqual(3, $incSketch->estimate('x'), 'Estimate for x should be 3');

// --------------------
// Test 5: Invalid parameters (exception handling)
// --------------------
$exceptionCaught = false;
try {
    new CountMinSketch(0, 5);
} catch (InvalidArgumentException $e) {
    $exceptionCaught = true;
}
assertTrue($exceptionCaught, 'Creating sketch with zero width should throw');

$exceptionCaught = false;
try {
    $cms->add('bad', -1);
} catch (InvalidArgumentException $e) {
    $exceptionCaught = true;
}
assertTrue($exceptionCaught, 'Negative increment should throw');

$exceptionCaught = false;
try {
    $cms->getHeavyHitters(1.0);
} catch (InvalidArgumentException $e) {
    $exceptionCaught = true;
}
assertTrue($exceptionCaught, 'Phi equal to 1 should throw');

// --------------------
// Helper assertions for boolean checks
// --------------------
function assertTrue(bool $cond, string $msg = ''): void
{
    assert($cond, $msg ?: 'Condition expected to be true');
}
function assertFalse(bool $cond, string $msg = ''): void
{
    assert(!$cond, $msg ?: 'Condition expected to be false');
}

// If script reaches this point without assertion failures, all tests passed.
echo "All Count‑Min Sketch tests passed.\n";
