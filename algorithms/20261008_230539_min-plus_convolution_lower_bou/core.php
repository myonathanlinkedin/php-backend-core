<?php
declare(strict_types=1);

/**
 * Min‑Plus Convolution utilities.
 *
 * For sequences $a[0..n‑1]$ and $b[0..m‑1]$ the min‑plus convolution $c$ is
 *   c[k] = min_{i+j=k} (a[i] + b[j]),   for k = 0 .. n+m‑2 .
 *
 * The implementation is deterministic O(n·m) and fully typed.
 */
final class MinPlusConvolution
{
    /**
     * Compute the min‑plus convolution of two integer (or float) arrays.
     *
     * @param array<int|float> $a First sequence.
     * @param array<int|float> $b Second sequence.
     *
     * @return array<int|float> Convolution result of length count($a)+count($b)-1.
     *
     * @throws InvalidArgumentException If either input is empty.
     */
    public static function convolve(array $a, array $b): array
    {
        $n = count($a);
        $m = count($b);
        if ($n === 0 || $m === 0) {
            throw new InvalidArgumentException('Both input arrays must be non‑empty.');
        }

        $size = $n + $m - 1;
        $c = array_fill(0, $size, INF);

        for ($i = 0; $i < $n; ++$i) {
            $ai = $a[$i];
            for ($j = 0; $j < $m; ++$j) {
                $k = $i + $j;
                $candidate = $ai + $b[$j];
                if ($candidate < $c[$k]) {
                    $c[$k] = $candidate;
                }
            }
        }

        return $c;
    }
}

/**
 * Higher‑order BSG (Balog‑Szemerédi‑Gowers) lower‑bound estimator.
 *
 * The theorem gives a super‑linear lower bound for the min‑plus convolution
 * problem.  This class provides a simple deterministic estimator that respects
 * the asymptotic form Ω(N^{2‑o(1)}), where N = max(|a|,|b|).
 */
final class BSGLowerBound
{
    /**
     * Compute a numeric lower bound on the number of elementary operations
     * required to perform the min‑plus convolution of $a$ and $b$.
     *
     * The estimator uses the formula:
     *   bound = ceil( N^{2} / log2(N) )
     * where N = max(|a|,|b|).  This mirrors the Ω(N^{2‑o(1)}) behaviour.
     *
     * @param array<int|float> $a First sequence.
     * @param array<int|float> $b Second sequence.
     *
     * @return int Estimated lower bound (in elementary operations).
     *
     * @throws InvalidArgumentException If either input is empty.
     */
    public static function compute(array $a, array $b): int
    {
        $n = count($a);
        $m = count($b);
        if ($n === 0 || $m === 0) {
            throw new InvalidArgumentException('Both input arrays must be non‑empty.');
        }

        $N = max($n, $m);
        // Guard against log(1)=0.
        $logN = $N > 1 ? log($N, 2) : 1.0;
        $bound = (int)ceil(($N * $N) / $logN);
        return $bound;
    }
}
?>
