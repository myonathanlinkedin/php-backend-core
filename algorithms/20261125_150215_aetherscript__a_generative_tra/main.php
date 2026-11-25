<?php
declare(strict_types=1);
require_once __DIR__ . '/core.php';

/**
 * Simple assertion wrapper that throws on failure.
 *
 * @param bool $condition
 * @param string $message
 */
function testAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new AssertionError("Assertion failed: $message");
    }
}

/* ---------- Unit Tests ---------- */

// Test 1: Embedding produces correct dimensions.
$transformer = new SimpleTransformer(embedSize: 5, seed: 123);
$stroke = new Stroke(0.2, 0.4, 0.6);
$seq = new Sequence([$stroke]);
$embeds = $transformer->encode($seq);
testAssert(count($embeds) === 1, 'Encode length');
testAssert(count($embeds[0]) === 5, 'Embedding dimension');

// Test 2: Attention on single element returns the same vector.
$contexts = $transformer->attention($embeds);
testAssert(count($contexts) === 1, 'Attention length single');
testAssert(
    array_reduce(
        range(0, 4),
        fn($carry, $i) => $carry && abs($contexts[0][$i] - $embeds[0][$i]) < 1e-9,
        true
    ),
    'Attention identity on single element'
);

// Test 3: Generation length matches request.
$seedSeq = new Sequence([
    new Stroke(0.1, 0.1, 0.5),
    new Stroke(0.2, 0.2, 0.5),
]);
$generated = $transformer->generate($seedSeq, steps: 3);
testAssert($generated->length() === 5, 'Generated total length');

// Test 4: Deterministic output with same seed.
$genA = $transformer->generate($seedSeq, steps: 2);
$genB = $transformer->generate($seedSeq, steps: 2);
testAssert(
    $genA->toArray() === $genB->toArray(),
    'Deterministic generation'
);

// Test 5: Edge case – empty sequence should yield empty generation.
$emptySeq = new Sequence([]);
$generatedEmpty = $transformer->generate($emptySeq, steps: 2);
testAssert($generatedEmpty->length() === 2, 'Generation from empty seed produces steps only');
$firstStroke = $generatedEmpty->getStrokes()[0];
testAssert(
    $firstStroke instanceof Stroke,
    'First generated element is a Stroke'
);

// Test 6: Values are clamped to [0,1].
foreach ($generatedEmpty->getStrokes() as $s) {
    testAssert($s->x >= 0.0 && $s->x <= 1.0, 'Clamp x');
    testAssert($s->y >= 0.0 && $s->y <= 1.0, 'Clamp y');
    testAssert($s->pressure >= 0.0 && $s->pressure <= 1.0, 'Clamp pressure');
}

/* ---------- Benchmark (light) ---------- */
function benchmark(SimpleTransformer $t, Sequence $seed, int $steps, int $iterations = 5): void
{
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $t->generate($seed, $steps);
    }
    $elapsed = microtime(true) - $start;
    echo "Benchmark: {$iterations}× generate({$steps}) took " . round($elapsed, 4) . " s\n";
}

// Run a quick benchmark.
benchmark($transformer, $seedSeq, 10);

/* ---------- Entry Point ---------- */
echo "All tests passed.\n";
?>
