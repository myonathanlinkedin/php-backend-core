<?php
declare(strict_types=1);
require_once __DIR__ . '/core.php';

/* ---------- Unit Tests ---------- */
function testTriangle(): void
{
    $g = new Graph();
    $g->addEdge(1, 2);
    $g->addEdge(2, 3);
    $g->addEdge(3, 1);

    assert(VertexCoverSolver::existsVertexCover($g, 2, 2) === true);
    assert(VertexCoverSolver::existsVertexCover($g, 1, 2) === false);
}
function testPathFour(): void
{
    $g = new Graph();
    $g->addEdge(1, 2);
    $g->addEdge(2, 3);
    $g->addEdge(3, 4);

    // Minimum vertex cover size = 2
    assert(VertexCoverSolver::existsVertexCover($g, 2, 3) === true);
    assert(VertexCoverSolver::existsVertexCover($g, 1, 3) === false);
}
function testStar(): void
{
    $g = new Graph();
    $center = 0;
    for ($i = 1; $i <= 5; ++$i) {
        $g->addEdge($center, $i);
    }

    // Minimum vertex cover size = 1 (the center)
    assert(VertexCoverSolver::existsVertexCover($g, 1, 2) === true);
    assert(VertexCoverSolver::existsVertexCover($g, 0, 2) === false);
}
function testEmpty(): void
{
    $g = new Graph();
    assert(VertexCoverSolver::existsVertexCover($g, 0, 1) === true);
}

/* ---------- Run Tests ---------- */
testTriangle();
testPathFour();
testStar();
testEmpty();

/* ---------- Simple Benchmark ---------- */
function benchmark(int $n, int $maxDepth): void
{
    $g = new Graph();
    // Build a random sparse graph (path) of length n
    for ($i = 0; $i < $n - 1; ++$i) {
        $g->addEdge($i, $i + 1);
    }

    $k = intdiv($n, 2); // known cover size for a path
    $start = microtime(true);
    $result = VertexCoverSolver::existsVertexCover($g, $k, $maxDepth);
    $elapsed = microtime(true) - $start;
    printf("Benchmark n=%d, maxDepth=%d: result=%s, time=%.6f s\n",
        $n, $maxDepth, $result ? 'true' : 'false', $elapsed);
}

benchmark(1000, 10);
benchmark(5000, 15);
?>
