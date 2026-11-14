<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Enable strict assertions
assert_options(ASSERT_ACTIVE,   true);
assert_options(ASSERT_BAIL,     true);
assert_options(ASSERT_WARNING, false);
assert_options(ASSERT_CALLBACK, static function (int $file, int $line, string $code, string $desc = null) {
    throw new AssertionError("Assertion failed at $file:$line: $code");
});

/**
 * Helper to generate a random alphanumeric string of given length.
 *
 * @param int $len
 *
 * @return string
 */
function randomString(int $len): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $max   = strlen($chars) - 1;
    $result = '';
    for ($i = 0; $i < $len; ++$i) {
        $result .= $chars[random_int(0, $max)];
    }
    return $result;
}

/* ---------- Unit Tests ---------- */

// Test 1: Basic add / contains functionality
$bf = new BloomFilter(1024, 3);
$items = ['apple', 'banana', 'cherry', 'date', 'elderberry'];
foreach ($items as $it) {
    $bf->add($it);
}
foreach ($items as $it) {
    assert($bf->contains($it) === true, "Item $it should be reported present");
}

// Test 2: Non‑added element should usually be absent (allowing false positives)
$absent = 'fig';
$present = $bf->contains($absent);
assert(is_bool($present), 'contains must return bool');

// Test 3: False‑positive rate estimation matches empirical measurement
$bf2 = new BloomFilter(8192, 4);
$nInserted = 1000;
for ($i = 0; $i < $nInserted; ++$i) {
    $bf2->add(randomString(12));
}
$tests = 5000;
$falsePositives = 0;
for ($i = 0; $i < $tests; ++$i) {
    $candidate = randomString(12);
    // Ensure candidate was not inserted (very low collision probability)
    if ($bf2->contains($candidate)) {
        $falsePositives++;
    }
}
$empirical = $falsePositives / $tests;
$theoretical = $bf2->falsePositiveProbability($nInserted);
$delta = abs($empirical - $theoretical);
assert($delta < 0.02, "Empirical FP rate $empirical deviates from theoretical $theoretical");

// Test 4: Edge case – minimal filter (size 1, 1 hash)
$bfSmall = new BloomFilter(1, 1);
$bfSmall->add('x');
assert($bfSmall->contains('x') === true);
assert($bfSmall->contains('y') === true, 'With size 1, every query must be true');

// Test 5: Invalid construction parameters raise exceptions
$exceptionThrown = false;
try {
    new BloomFilter(0, 3);
} catch (InvalidArgumentException $e) {
    $exceptionThrown = true;
}
assert($exceptionThrown === true, 'Zero size should throw');

$exceptionThrown = false;
try {
    new BloomFilter(128, 0);
} catch (InvalidArgumentException $e) {
    $exceptionThrown = true;
}
assert($exceptionThrown === true, 'Zero hash count should throw');

// Test 6: False‑positive probability with zero inserted items must be zero
$bfZero = new BloomFilter(256, 3);
assert($bfZero->falsePositiveProbability(0) === 0.0);

// If script reaches this point, all tests passed.
echo "All BloomFilter unit tests passed.\n";
