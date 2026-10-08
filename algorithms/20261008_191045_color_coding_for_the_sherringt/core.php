<?php
declare(strict_types=1);

/**
 * Simple undirected graph using adjacency lists.
 */
final class Graph
{
    /** @var array<int, array<int, bool>> adjacency list */
    private array $adj = [];

    /** @var int number of vertices */
    private int $n = 0;

    public function __construct(int $numVertices)
    {
        if ($numVertices < 0) {
            throw new InvalidArgumentException('Number of vertices cannot be negative');
        }
        $this->n = $numVertices;
        for ($i = 0; $i < $numVertices; $i++) {
            $this->adj[$i] = [];
        }
    }

    public function addEdge(int $u, int $v): void
    {
        $this->validateVertex($u);
        $this->validateVertex($v);
        if ($u === $v) {
            // ignore self‑loops for simple paths
            return;
        }
        $this->adj[$u][$v] = true;
        $this->adj[$v][$u] = true;
    }

    /** @return array<int, array<int, bool>> */
    public function getAdjacency(): array
    {
        return $this->adj;
    }

    public function vertexCount(): int
    {
        return $this->n;
    }

    private function validateVertex(int $v): void
    {
        if ($v < 0 || $v >= $this->n) {
            throw new OutOfBoundsException("Vertex $v is out of bounds");
        }
    }
}

/**
 * Color Coding algorithm for counting simple paths of exactly k vertices.
 *
 * The algorithm is randomized; to obtain a deterministic result we fix the random seed.
 * The returned value is an unbiased estimator of the exact count.
 */
final class ColorCoding
{
    private Graph $graph;
    private int $k;               // length in vertices
    private int $iterations;      // number of repetitions R
    private int $seed;            // random seed for reproducibility

    /**
     * @param Graph $graph
     * @param int   $k          number of vertices in the path (k ≥ 1)
     * @param int   $iterations number of repetitions (R ≥ 1)
     * @param int   $seed       random seed
     */
    public function __construct(Graph $graph, int $k, int $iterations = 1000, int $seed = 12345)
    {
        if ($k < 1) {
            throw new InvalidArgumentException('k must be at least 1');
        }
        if ($iterations < 1) {
            throw new InvalidArgumentException('iterations must be at least 1');
        }
        $this->graph = $graph;
        $this->k = $k;
        $this->iterations = $iterations;
        $this->seed = $seed;
    }

    /**
     * Returns an unbiased estimator of the number of simple paths of exactly k vertices.
     *
     * Time complexity: O(R * k * (n + m) * 2^k)
     * Space complexity: O(n * 2^k)
     *
     * @return float estimated count
     */
    public function estimatePathCount(): float
    {
        $n = $this->graph->vertexCount();
        if ($this->k > $n) {
            return 0.0;
        }

        $fullMask = (1 << $this->k) - 1;
        $totalColorful = 0.0;

        // deterministic pseudo‑random generator
        mt_srand($this->seed);

        for ($rep = 0; $rep < $this->iterations; $rep++) {
            // 1. Random coloring of vertices with colors 0..k-1
            $color = [];
            for ($v = 0; $v < $n; $v++) {
                $color[$v] = mt_rand(0, $this->k - 1);
            }

            // 2. DP table: dp[v][mask] = number of colorful paths ending at v covering colors in mask
            // Initialise with empty arrays for memory efficiency
            $dp = array_fill(0, $n, []);
            for ($v = 0; $v < $n; $v++) {
                $c = $color[$v];
                $mask = 1 << $c;
                $dp[$v][$mask] = 1; // path consisting of the single vertex v
            }

            // 3. Iterate over subset sizes from 2 to k
            for ($size = 2; $size <= $this->k; $size++) {
                // For each vertex v, we will try to extend paths that end at its neighbours
                $newDp = array_fill(0, $n, []);
                foreach ($this->graph->getAdjacency() as $v => $neighbors) {
                    $cV = $color[$v];
                    $bitV = 1 << $cV;
                    // Enumerate all masks of size $size that contain bitV
                    // We generate masks by iterating over all subsets of colors of size $size-1
                    // and then adding bitV.
                    // To keep implementation simple, we iterate over all masks of size $size
                    // and test inclusion of bitV.
                    $maxMask = $fullMask;
                    for ($mask = $maxMask; $mask > 0; $mask--) {
                        if (($mask & $bitV) === 0) {
                            continue; // mask must contain color of v
                        }
                        if (self::popcount($mask) !== $size) {
                            continue;
                        }
                        $prevMask = $mask ^ $bitV; // mask without v's color
                        $sum = 0;
                        foreach ($neighbors as $u => $_) {
                            if (isset($dp[$u][$prevMask])) {
                                $sum += $dp[$u][$prevMask];
                            }
                        }
                        if ($sum > 0) {
                            $newDp[$v][$mask] = $sum;
                        }
                    }
                }
                $dp = $newDp;
            }

            // 4. Count colorful paths of full size
            $colorfulThisIter = 0;
            foreach ($dp as $v => $maskMap) {
                if (isset($maskMap[$fullMask])) {
                    $colorfulThisIter += $maskMap[$fullMask];
                }
            }
            $totalColorful += $colorfulThisIter;
        }

        // Expected number of colorful paths = totalColorful / R
        // Each simple path becomes colorful with probability k! / k^k
        $probColorful = self::factorial($this->k) / pow($this->k, $this->k);
        if ($probColorful == 0.0) {
            return 0.0;
        }
        $estimate = ($totalColorful / $this->iterations) / $probColorful;
        return $estimate;
    }

    /** @return int factorial of small k (k ≤ 20 fits in 64‑bit) */
    private static function factorial(int $x): int
    {
        $res = 1;
        for ($i = 2; $i <= $x; $i++) {
            $res *= $i;
        }
        return $res;
    }

    /** @return int number of set bits in an integer */
    private static function popcount(int $x): int
    {
        $cnt = 0;
        while ($x) {
            $cnt += $x & 1;
            $x >>= 1;
        }
        return $cnt;
    }
}
?>
