<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

// Enable strict assertions
assert_options(ASSERT_ACTIVE,   1);
assert_options(ASSERT_EXCEPTION, 1);
assert_options(ASSERT_WARNING,   0);

/**
 * Helper: compute exact cardinality of an array (unique count).
 */
function exactCardinality(array $elements): int
{
    return count(array_unique($elements, SORT_STRING));
}

/**
 * Test 1 – Empty sketch should estimate 0.
 */
$hll = createHyperLogLog(10);
assert($hll->estimate() === 0.0, 'Empty sketch estimate should be 0');

/**
 * Test 2 – Small cardinalities (≤ 1000) should be accurate within 5 %.
 */
$precision = 12;
$hll = createHyperLogLog($precision);
$elements = [];
for ($i = 0; $i < 500; $i++) {
    $elem = 'elem_' . $i;
    $elements[] = $elem;
    $hll->add($elem);
}
$exact = exactCardinality($elements);
$estimate = $hll->estimate();
$relativeError = abs($estimate - $exact) / $exact;
assert($relativeError < 0.05, "Relative error {$relativeError} exceeds 5% for 500 distinct elements");

/**
 * Test 3 – Adding duplicates must not inflate the estimate.
 */
$hllDup = createHyperLogLog($precision);
foreach ($elements as $e) {
    $hllDup->add($e);
    $hllDup->add($e); // duplicate
}
$estimateDup = $hllDup->estimate();
$relativeErrorDup = abs($estimateDup - $exact) / $exact;
assert($relativeErrorDup < 0.05, 'Duplicate insertion altered estimate beyond tolerance');

/**
 * Test 4 – Large cardinality (≈ 100 000) should stay within theoretical error bound.
 * Theoretical standard error ≈ 1.04 / sqrt(m)
 */
$largePrecision = 14; // m = 16384
$hllLarge = createHyperLogLog($largePrecision);
$largeSet = [];
$target = 100_000;
for ($i = 0; $i < $target; $i++) {
    $val = 'uid_' . $i;
    $largeSet[] = $val;
    $hllLarge->add($val);
}
$exactLarge = exactCardinality($largeSet);
$estimateLarge = $hllLarge->estimate();
$relativeErrorLarge = abs($estimateLarge - $exactLarge) / $exactLarge;
$theoreticalStdErr = 1.04 / sqrt(1 << $largePrecision);
assert($relativeErrorLarge < 3 * $theoreticalStdErr, 'Large set error exceeds 3× theoretical std error');

/**
 * Test 5 – Merge operation (union of two sketches).
 */
$hllA = createHyperLogLog(12);
$hllB = createHyperLogLog(12);
$setA = [];
$setB = [];
for ($i = 0; $i < 300; $i++) {
    $a = "a_$i";
    $b = "b_$i";
    $setA[] = $a;
    $setB[] = $b;
    $hllA->add($a);
    $hllB->add($b);
}
$unionExact = exactCardinality(array_merge($setA, $setB));
$hllA->merge($hllB);
$unionEstimate = $hllA->estimate();
$relativeErrorUnion = abs($unionEstimate - $unionExact) / $unionExact;
assert($relativeErrorUnion < 0.07, 'Union merge error exceeds 7%');

// Demo output (optional, not part of assertions)
echo "All HyperLogLog tests passed.\n";
echo "Example estimate for 100 000 distinct items: " . round($estimateLarge) . "\n";
?>
