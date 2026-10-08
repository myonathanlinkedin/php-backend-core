<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/core.php';

// Configure assertions to abort on first failure.
assert_options(ASSERT_BAIL, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_CALLBACK, static function (int $file, int $line, string $code, string $desc = null) {
    throw new AssertionError("Assertion failed at {$file}:{$line} – {$code}");
});

/**
 * Helper to compare two numeric arrays with tolerance for floating point values.
 */
function arrays_are_equal(array $x, array $y, float $eps = 1e-9): bool
{
    if (count($x) !== count($y)) {
        return false;
    }
    foreach ($x as $i => $vx) {
        $vy = $y[$i];
        if (is_float($vx) || is_float($vy)) {
            if (abs($vx - $vy) > $eps) {
                return false;
            }
        } else {
            if ($vx !== $vy) {
                return false;
            }
        }
    }
    return true;
}

/* ---------- Unit Tests for MinPlusConvolution ---------- */
function test_convolution_basic(): void
{
    $a = [1, 2, 3];
    $b = [4, 5];
    // Expected: [1+4, min(1+5,2+4), min(2+5,3+4), 3+5] = [5,6,7,8]
    $expected = [5, 6, 7, 8];
    $result = MinPlusConvolution::convolve($a, $b);
    assert(arrays_are_equal($result, $expected), 'Basic convolution failed');
}
test_convolution_basic();

function test_convolution_singletons(): void
{
    $a = [7];
    $b = [3];
    $expected = [10];
    $result = MinPlusConvolution::convolve($a, $b);
    assert(arrays_are_equal($result, $expected), 'Singleton convolution failed');
}
test_convolution_singletons();

function test_convolution_negative_numbers(): void
{
    $a = [-2, 0, 5];
    $b = [3, -1];
    // Compute manually:
    // k=0: -2+3 = 1
    // k=1: min(-2-1,0+3) = -3
    // k=2: min(0-1,5+3) = -1
    // k=3: 5-1 = 4
    $expected = [1, -3, -1, 4];
    $result = MinPlusConvolution::convolve($a, $b);
    assert(arrays_are_equal($result, $expected), 'Negative numbers convolution failed');
}
test_convolution_negative_numbers();

function test_convolution_large_random(): void
{
    $n = 50;
    $m = 40;
    $a = [];
    $b = [];
    for ($i = 0; $i < $n; ++$i) {
        $a[] = random_int(-1000, 1000);
    }
    for ($j = 0; $j < $m; ++$j) {
        $b[] = random_int(-1000, 1000);
    }

    // Naïve O(n·m) verification (reuse same implementation, but cross‑check with a slower double loop).
    $expected = array_fill(0, $n + $m - 1, INF);
    for ($i = 0; $i < $n; ++$i) {
        for ($j = 0; $j < $m; ++$j) {
            $k = $i + $j;
            $candidate = $a[$i] + $b[$j];
            if ($candidate < $expected[$k]) {
                $expected[$k] = $candidate;
            }
        }
    }

    $result = MinPlusConvolution::convolve($a, $b);
    assert(arrays_are_equal($result, $expected), 'Large random convolution mismatch');
}
test_convolution_large_random();

/* ---------- Unit Tests for BSGLowerBound ---------- */
function test_bsg_lower_bound_basic(): void
{
    $a = [1, 2, 3];
    $b = [4, 5];
    $bound = BSGLowerBound::compute($a, $b);
    // N = max(3,2) = 3 => bound = ceil(9 / log2(3)) ≈ ceil(9 / 1.585) = 6
    assert($bound === 6, 'BSG lower bound basic case failed');
}
test_bsg_lower_bound_basic();

function test_bsg_lower_bound_edge_one_element(): void
{
    $a = [0];
    $b = [0];
    $bound = BSGLowerBound::compute($a, $b);
    // N = 1 => logN forced to 1 => bound = ceil(1/1) = 1
    assert($bound === 1, 'BSG lower bound single element failed');
}
test_bsg_lower_bound_edge_one_element();

function test_bsg_lower_bound_monotonicity(): void
{
    $aSmall = [1, 2];
    $bSmall = [3];
    $aLarge = range(1, 100);
    $bLarge = range(1, 100);

    $boundSmall = BSGLowerBound::compute($aSmall, $bSmall);
    $boundLarge = BSGLowerBound::compute($aLarge, $bLarge);
    assert($boundLarge > $boundSmall, 'BSG lower bound monotonicity failed');
}
test_bsg_lower_bound_monotonicity();

echo "All tests passed.\n";
?>
