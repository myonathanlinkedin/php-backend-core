<?php
declare(strict_types=1);
require_once __DIR__ . '/engine.php';

// Enable strict assertions.
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_BAIL, 0);
assert_options(ASSERT_CALLBACK, static function (int $file, int $line, string $code, ?string $desc = null) {
    $msg = "Assertion failed at {$file}:{$line} – {$code}";
    if ($desc !== null) {
        $msg .= " – {$desc}";
    }
    throw new AssertionError($msg);
});

/**
 * Checks internal consistency of a MatchingResult.
 */
function verifyResult(MatchingResult $res, BipartiteGraph $g): void
{
    $count = 0;
    foreach ($res->pairU as $u => $v) {
        if ($v !== -1) {
            ++$count;
            assert($res->pairV[$v] === $u, "pairV[$v] should be $u");
        }
    }
    assert($count === $res->size, "Matching size mismatch");
    foreach ($res->pairU as $v) {
        if ($v !== -1) {
            assert($v >= 0 && $v < $g->nRight, "pairU contains invalid right index");
        }
    }
    foreach ($res->pairV as $u) {
        if ($u !== -1) {
            assert($u >= 0 && $u < $g->nLeft, "pairV contains invalid left index");
        }
    }
}

/* ---------- Test cases ---------- */

// 1. Empty graph.
$g0 = new BipartiteGraph(0, 0);
$res0 = (new HopcroftKarp($g0))->maxMatching();
assert($res0->size === 0, 'Empty graph size');
verifyResult($res0, $g0);

// 2. Single edge.
$g1 = new BipartiteGraph(1, 1);
$g1->addEdge(0, 0);
$res1 = (new HopcroftKarp($g1))->maxMatching();
assert($res1->size === 1, 'Single edge size');
verifyResult($res1, $g1);

// 3. Small graph with multiple augmenting paths.
$g2 = new BipartiteGraph(2, 2);
$g2->addEdge(0, 0);
$g2->addEdge(0, 1);
$g2->addEdge(1, 0);
$res2 = (new HopcroftKarp($g2))->maxMatching();
assert($res2->size === 2, 'Graph 2 size');
verifyResult($res2, $g2);

// 4. Chain graph (perfect matching of size 3).
$g3 = new BipartiteGraph(3, 3);
$g3->addEdge(0, 0);
$g3->addEdge(1, 0);
$g3->addEdge(1, 1);
$g3->addEdge(2, 1);
$g3->addEdge(2, 2);
$res3 = (new HopcroftKarp($g3))->maxMatching();
assert($res3->size === 3, 'Chain graph size');
verifyResult($res3, $g3);

// 5. No edges.
$g4 = new BipartiteGraph(2, 2);
$res4 = (new HopcroftKarp($g4))->maxMatching();
assert($res4->size === 0, 'No‑edge graph size');
verifyResult($res4, $g4);

// 6. Larger deterministic graph.
$g5 = new BipartiteGraph(4, 5);
$edges = [
    [0, 0], [0, 1],
    [1, 0], [1, 2], [1, 3],
    [2, 1], [2, 3], [2, 4],
    [3, 2], [3, 4],
];
foreach ($edges as $e) {
    $g5->addEdge($e[0], $e[1]);
}
$res5 = (new HopcroftKarp($g5))->maxMatching();
assert($res5->size === 4, 'Larger graph size should be 4');
verifyResult($res5, $g5);

// Demo output (not part of assertions).
echo "All Hopcroft‑Karp tests passed.\n";
echo "Matching sizes: g0={$res0->size}, g1={$res1->size}, g2={$res2->size}, g3={$res3->size}, g4={$res4->size}, g5={$res5->size}\n";
?>
