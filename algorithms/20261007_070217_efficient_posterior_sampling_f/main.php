<?php
declare(strict_types=1);
require_once __DIR__ . '/engine.php';

// Seed for reproducibility
mt_srand(12345);

// Helper to compare float with tolerance
function assertClose(float $actual, float $expected, float $tol, string $msg = ''): void
{
    assert(abs($actual - $expected) <= $tol, $msg . " Expected $expected, got $actual");
}

// Test 1: Low noise should recover true labels with high confidence
{
    $n = 6;
    $p = 0.1; // low noise
    [$g, $true] = generateSyntheticInstance($n, $p);
    $beta = computeBeta($p);
    // Random initialization
    $init = [];
    for ($i = 0; $i < $n; $i++) {
        $init[$i] = (mt_rand(0, 1) === 1) ? 1 : -1;
    }
    $marginals = runGibbs($g, $init, $beta, iterations: 5000, burnIn: 1000, thin: 5);
    // Verify that each marginal is >0.7 for true +1 and <0.3 for true -1
    foreach ($true as $i => $label) {
        $prob = $marginals[$i];
        if ($label === 1) {
            assertClose($prob, 1.0, 0.3, "Node $i (+1) marginal not close to 1");
            assert($prob > 0.7, "Node $i (+1) marginal $prob <= 0.7");
        } else {
            assertClose($prob, 0.0, 0.3, "Node $i (-1) marginal not close to 0");
            assert($prob < 0.3, "Node $i (-1) marginal $prob >= 0.3");
        }
    }
}

// Test 2: High noise (p=0.5) yields uninformative marginals ~0.5
{
    $n = 5;
    $p = 0.5; // pure noise
    [$g, $true] = generateSyntheticInstance($n, $p);
    $beta = computeBeta($p); // should be 0
    $init = array_fill(0, $n, 1);
    $marginals = runGibbs($g, $init, $beta, iterations: 2000, burnIn: 500, thin: 5);
    foreach ($marginals as $i => $prob) {
        assertClose($prob, 0.5, 0.1, "Node $i marginal not near 0.5 under pure noise");
    }
}

// Test 3: Verify that computeBeta throws on invalid p
{
    $exceptionThrown = false;
    try {
        computeBeta(0.0);
    } catch (InvalidArgumentException $e) {
        $exceptionThrown = true;
    }
    assert($exceptionThrown, 'computeBeta should throw for p=0');

    $exceptionThrown = false;
    try {
        computeBeta(1.0);
    } catch (InvalidArgumentException $e) {
        $exceptionThrown = true;
    }
    assert($exceptionThrown, 'computeBeta should throw for p=1');
}

// Demo: Run a small instance and print marginals
{
    $n = 4;
    $p = 0.2;
    [$g, $true] = generateSyntheticInstance($n, $p);
    $beta = computeBeta($p);
    $init = array_fill(0, $n, 1);
    $marginals = runGibbs($g, $init, $beta, iterations: 3000, burnIn: 500, thin: 5);
    echo "True labels: " . implode(' ', $true) . PHP_EOL;
    echo "Estimated +1 probabilities: " . implode(' ', array_map(fn($v) => sprintf('%.3f', $v), $marginals)) . PHP_EOL;
}
?>
