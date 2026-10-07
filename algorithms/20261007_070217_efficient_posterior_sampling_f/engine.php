<?php
declare(strict_types=1);
require_once __DIR__ . '/types.php';

/**
 * Compute the interaction strength beta from noise probability p.
 *
 * @param float $p Probability that a measurement is flipped (0 < p < 1)
 * @return float
 */
function computeBeta(float $p): float
{
    if ($p <= 0.0 || $p >= 1.0) {
        throw new InvalidArgumentException('Noise probability p must be in (0,1).');
    }
    return log((1.0 - $p) / $p);
}

/**
 * Sample a new label for node $i given current configuration $x.
 *
 * @param int $i Node index
 * @param Graph $g Graph
 * @param array<int, int> $x Current labels (+1 / -1)
 * @param float $beta Interaction strength
 * @return int New label (+1 or -1)
 */
function sampleConditional(int $i, Graph $g, array $x, float $beta): int
{
    $sum = 0;
    foreach ($g->neighbors($i) as $j => $edge) {
        $sum += $edge->y * $x[$j];
    }
    $logit = $beta * $sum;
    $probPos = 1.0 / (1.0 + exp(-2.0 * $logit)); // P(x_i = +1)
    $rand = mt_rand() / mt_getrandmax();
    return ($rand < $probPos) ? 1 : -1;
}

/**
 * Perform one full Gibbs sweep over all nodes (in random order).
 *
 * @param Graph $g
 * @param array<int, int> $x Current labels
 * @param float $beta
 * @return array<int, int> Updated labels
 */
function gibbsSweep(Graph $g, array $x, float $beta): array
{
    $order = range(0, $g->n - 1);
    shuffle($order);
    foreach ($order as $i) {
        $x[$i] = sampleConditional($i, $g, $x, $beta);
    }
    return $x;
}

/**
 * Run Gibbs sampling and compute marginal probabilities for each node.
 *
 * @param Graph $g
 * @param array<int, int> $init Initial labels
 * @param float $beta
 * @param int $iterations Total number of sweeps
 * @param int $burnIn Number of initial sweeps to discard
 * @param int $thin Thinning interval (collect one sample every $thin sweeps)
 * @return array<int, float> Marginal probability of +1 for each node
 */
function runGibbs(
    Graph $g,
    array $init,
    float $beta,
    int $iterations,
    int $burnIn,
    int $thin
): array {
    if ($iterations <= $burnIn) {
        throw new InvalidArgumentException('iterations must be greater than burnIn.');
    }
    $x = $init;
    $counts = array_fill(0, $g->n, 0);
    $samplesCollected = 0;

    for ($t = 0; $t < $iterations; $t++) {
        $x = gibbsSweep($g, $x, $beta);
        if ($t >= $burnIn && (($t - $burnIn) % $thin) === 0) {
            for ($i = 0; $i < $g->n; $i++) {
                if ($x[$i] === 1) {
                    $counts[$i] += 1;
                }
            }
            $samplesCollected++;
        }
    }

    $marginals = [];
    for ($i = 0; $i < $g->n; $i++) {
        $marginals[$i] = $samplesCollected > 0 ? $counts[$i] / $samplesCollected : 0.5;
    }
    return $marginals;
}

/**
 * Generate synthetic Z2 synchronization instance.
 *
 * @param int $n Number of nodes
 * @param float $p Noise probability
 * @return array{Graph, array<int, int>} Tuple of graph and true labels
 */
function generateSyntheticInstance(int $n, float $p): array
{
    // True labels uniformly random +1 / -1
    $trueLabels = [];
    for ($i = 0; $i < $n; $i++) {
        $trueLabels[$i] = (mt_rand(0, 1) === 1) ? 1 : -1;
    }

    $g = new Graph($n);
    // Create a complete graph for simplicity
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            // Measurement y_ij = trueLabels[i] * trueLabels[j] flipped with prob p
            $correct = $trueLabels[$i] * $trueLabels[$j];
            $flip = (mt_rand() / mt_getrandmax()) < $p;
            $y = $flip ? -$correct : $correct;
            $g->addEdge($i, $j, $y);
        }
    }

    return [$g, $trueLabels];
}
