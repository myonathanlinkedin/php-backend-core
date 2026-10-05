<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

use QuantumPolyTransform\LegendrePolynomial;
use QuantumPolyTransform\TransformEngine;

/**
 * Simple assertion wrapper that throws on failure.
 *
 * @param bool   $condition Condition to assert.
 * @param string $message   Failure message.
 */
function testAssert(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new \AssertionError($message);
    }
}

/* -------------------- Unit Tests -------------------- */

// Test Legendre polynomial values against known analytical results.
function testLegendreValues(): void
{
    $leg = new LegendrePolynomial();

    // P0(x) = 1
    testAssert(abs($leg->evaluate(0, -0.5) - 1.0) < 1e-12, 'P0 failed');

    // P1(x) = x
    testAssert(abs($leg->evaluate(1, 0.7) - 0.7) < 1e-12, 'P1 failed');

    // P2(x) = (3x^2 - 1)/2
    $x = 0.3;
    $expected = (3 * $x * $x - 1) / 2;
    testAssert(abs($leg->evaluate(2, $x) - $expected) < 1e-12, 'P2 failed');

    // P3(x) = (5x^3 - 3x)/2
    $expected = (5 * $x * $x * $x - 3 * $x) / 2;
    testAssert(abs($leg->evaluate(3, $x) - $expected) < 1e-12, 'P3 failed');
}

// Test forward → inverse round‑trip reproduces the original signal.
function testForwardInverseRoundTrip(): void
{
    $poly = new LegendrePolynomial();
    $signal = [0.0, 1.2, -0.7, 3.4, 2.2]; // arbitrary small test vector

    $coeffs = TransformEngine::forwardTransform($signal, $poly);
    $recon  = TransformEngine::inverseTransform($coeffs, $poly);

    foreach ($signal as $idx => $orig) {
        testAssert(abs($orig - $recon[$idx]) < 1e-9, "Round‑trip mismatch at index $idx");
    }
}

// Test that the quantum placeholder returns identical coefficients.
function testQuantumPlaceholder(): void
{
    $poly = new LegendrePolynomial();
    $signal = [1.0, -0.5, 2.3, 0.0, 4.1, -1.2];

    $classical = TransformEngine::forwardTransform($signal, $poly);
    $quantum   = TransformEngine::quantumTransform($signal, $poly);

    foreach ($classical as $i => $c) {
        testAssert(abs($c - $quantum[$i]) < 1e-12, "Quantum placeholder mismatch at $i");
    }
}

/* -------------------- Benchmark -------------------- */

function benchmarkTransform(int $size = 1024): void
{
    $poly = new LegendrePolynomial();
    $signal = [];
    for ($i = 0; $i < $size; $i++) {
        $signal[] = random_int(-1000, 1000) / 1000.0;
    }

    $start = microtime(true);
    $coeffs = TransformEngine::forwardTransform($signal, $poly);
    $mid   = microtime(true);
    $recon  = TransformEngine::inverseTransform($coeffs, $poly);
    $end   = microtime(true);

    $forwardTime = $mid - $start;
    $inverseTime = $end - $mid;

    echo "Benchmark (N=$size):\n";
    echo "  Forward transform: " . sprintf('%.6f', $forwardTime) . " s\n";
    echo "  Inverse transform: " . sprintf('%.6f', $inverseTime) . " s\n";

    // Simple sanity check: reconstruction error should be small.
    $maxErr = 0.0;
    foreach ($signal as $i => $orig) {
        $err = abs($orig - $recon[$i]);
        if ($err > $maxErr) {
            $maxErr = $err;
        }
    }
    echo "  Max reconstruction error: " . sprintf('%.2e', $maxErr) . "\n";
}

/* -------------------- Entry Point -------------------- */

function runAllTests(): void
{
    testLegendreValues();
    testForwardInverseRoundTrip();
    testQuantumPlaceholder();
    echo "All unit tests passed.\n";
}

runAllTests();
benchmarkTransform(512); // Adjust size for quick demo.
