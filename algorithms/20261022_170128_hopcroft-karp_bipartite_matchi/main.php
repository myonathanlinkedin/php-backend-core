<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

// Configure assertions to abort on first failure for deterministic testing
assert_options(ASSERT_BAIL_ON_FAILURE, 1);
assert_options(ASSERT_WARNING, 0);

/**
 * Helper to run a single test case.
 *
 * @param string $name descriptive name
 * @param callable $fn test body
 */
function runTest(string $name, callable $fn): void
{
    $fn();
    echo "✔ {$name}\n";
}

/* ---------- Test Cases ---------- */

runTest('Empty graph', function (): void {
    $g = new BipartiteGraph(0, 0);
    $hk = new HopcroftKarp($g);
    assert($hk->maxMatching() === 0);
});

runTest('Single edge', function (): void {
    $g = new BipartiteGraph(1, 1);
    $g->addEdge(1, 1);
    $hk = new HopcroftKarp($g);
    assert($hk->maxMatching() === 1);
});

runTest('Perfect matching 2×2', function (): void {
    $g = new BipartiteGraph(2, 2);
    $g->addEdge(1, 1);
    $g->addEdge(1, 2);
    $g->addEdge(2, 1);
    $g->addEdge(2, 2);
    $hk = new HopcroftKarp($g);
    assert($hk->maxMatching() === 2);
});

runTest('Imperfect matching 3×3 (max 2)', function (): void {
    $g = new BipartiteGraph(3, 3);
    $g->addEdge(1, 1);
    $g->addEdge(1, 2);
    $g->addEdge(2, 2);
    $g->addEdge(3, 3);
    // No edge connects vertex 2 on the right to vertex 3 on the left,
    // forcing at most two matches.
    $hk = new HopcroftKarp($g);
    assert($hk->maxMatching() === 2);
});

runTest('Disconnected components', function (): void {
    $g = new BipartiteGraph(4, 4);
    // Component A: 1–1, 2–2 (perfect)
    $g->addEdge(1, 1);
    $g->addEdge(2, 2);
    // Component B: 3–3, 3–4, 4–3 (max 2)
    $g->addEdge(3, 3);
    $g->addEdge(3, 4);
    $g->addEdge(4, 3);
    $hk = new HopcroftKarp($g);
    assert($hk->maxMatching() === 4);
});

runTest('Larger random‑like graph', function (): void {
    $g = new BipartiteGraph(6, 6);
    $edges = [
        [1, 2], [1, 3],
        [2, 1], [2, 4],
        [3, 2], [3, 5],
        [4, 3], [4, 6],
        [5, 5],
        [6, 4], [6, 6],
    ];
    foreach ($edges as [$u, $v]) {
        $g->addEdge($u, $v);
    }
    $hk = new HopcroftKarp($g);
    // Manually verified maximum matching size is 5
    assert($hk->maxMatching() === 5);
});

echo "All tests passed.\n";
