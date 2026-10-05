<?php
declare(strict_types=1);

namespace QuantumPolyTransform;

/**
 * Interface for orthogonal polynomials.
 */
interface OrthogonalPolynomial
{
    /**
     * Evaluate the polynomial of given order at point x.
     *
     * @param int   $order Polynomial order (non‑negative).
     * @param float $x     Evaluation point in [-1, 1].
     *
     * @return float Value of P_order(x).
     */
    public function evaluate(int $order, float $x): float;
}

/**
 * Legendre polynomials P_n(x) on [-1,1].
 *
 * Recurrence:
 *   P_0(x) = 1
 *   P_1(x) = x
 *   (n+1) P_{n+1}(x) = (2n+1) x P_n(x) - n P_{n-1}(x)
 */
final class LegendrePolynomial implements OrthogonalPolynomial
{
    /**
     * {@inheritdoc}
     */
    public function evaluate(int $order, float $x): float
    {
        if ($order < 0) {
            throw new \InvalidArgumentException('Order must be non‑negative.');
        }
        if ($order === 0) {
            return 1.0;
        }
        if ($order === 1) {
            return $x;
        }

        $pPrev = 1.0;   // P_0
        $pCurr = $x;    // P_1

        for ($n = 1; $n < $order; $n++) {
            $pNext = ((2 * $n + 1) * $x * $pCurr - $n * $pPrev) / ($n + 1);
            $pPrev = $pCurr;
            $pCurr = $pNext;
        }

        return $pCurr;
    }
}

/**
 * Core engine for orthogonal polynomial transforms.
 *
 * The implementation uses a simple discrete inner‑product on uniformly
 * spaced points in [-1,1].  This is sufficient for unit‑testing and
 * benchmarking while remaining extensible to true quantum back‑ends.
 */
final class TransformEngine
{
    /**
     * Forward orthogonal polynomial transform.
     *
     * @param float[]               $values Input signal (size N).
     * @param OrthogonalPolynomial  $poly   Polynomial family.
     *
     * @return float[] Coefficients c_k, k = 0 … N‑1.
     */
    public static function forwardTransform(array $values, OrthogonalPolynomial $poly): array
    {
        $n = count($values);
        if ($n === 0) {
            return [];
        }

        $coeffs = array_fill(0, $n, 0.0);
        for ($k = 0; $k < $n; $k++) {
            $sum = 0.0;
            for ($i = 0; $i < $n; $i++) {
                $x = ($n > 1) ? (2.0 * $i / ($n - 1) - 1.0) : 0.0; // map i → [-1,1]
                $sum += $values[$i] * $poly->evaluate($k, $x);
            }
            $coeffs[$k] = $sum;
        }

        return $coeffs;
    }

    /**
     * Inverse orthogonal polynomial transform.
     *
     * @param float[]               $coeffs Coefficients from forwardTransform.
     * @param OrthogonalPolynomial  $poly   Polynomial family.
     *
     * @return float[] Reconstructed signal (size N).
     */
    public static function inverseTransform(array $coeffs, OrthogonalPolynomial $poly): array
    {
        $n = count($coeffs);
        if ($n === 0) {
            return [];
        }

        $recon = array_fill(0, $n, 0.0);
        for ($i = 0; $i < $n; $i++) {
            $x = ($n > 1) ? (2.0 * $i / ($n - 1) - 1.0) : 0.0;
            $value = 0.0;
            for ($k = 0; $k < $n; $k++) {
                $value += $coeffs[$k] * $poly->evaluate($k, $x);
            }
            $recon[$i] = $value;
        }

        return $recon;
    }

    /**
     * Placeholder for a quantum‑accelerated transform.
     *
     * In a production system this would dispatch to a quantum simulator
     * or hardware backend.  Here we simply reuse the classical forward
     * transform to keep the API stable.
     *
     * @param float[]               $values Input signal.
     * @param OrthogonalPolynomial  $poly   Polynomial family.
     *
     * @return float[] Coefficients.
     */
    public static function quantumTransform(array $values, OrthogonalPolynomial $poly): array
    {
        // Future quantum implementation goes here.
        // For now, fall back to the classical algorithm.
        return self::forwardTransform($values, $poly);
    }
}
