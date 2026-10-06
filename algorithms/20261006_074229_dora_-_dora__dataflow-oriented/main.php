<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

use DORA\{
    TransformNode,
    FilterNode,
    Pipeline,
    DataflowEngine,
    DORAException
};

/**
 * Simple assertion helper that throws on failure.
 */
function expect(bool $condition, string $message = ''): void
{
    assert($condition, $message);
}

/**
 * Unit tests for core components.
 */
function runTests(): void
{
    // Test TransformNode
    $doubleNode = new TransformNode('double', fn($x) => $x * 2);
    $input = [1, 2, 3];
    $expected = [2, 4, 6];
    expect($doubleNode->process($input) === $expected, 'TransformNode failed');

    // Test FilterNode
    $evenNode = new FilterNode('even', fn($x) => $x % 2 === 0);
    $input = [1, 2, 3, 4, 5];
    $expected = [2, 4];
    expect($evenNode->process($input) === $expected, 'FilterNode failed');

    // Test Pipeline composition
    $pipeline = new Pipeline();
    $pipeline->addNode($doubleNode);
    $pipeline->addNode($evenNode);
    $input = [1, 2, 3, 4];
    // double => [2,4,6,8]; filter even => [2,4,6,8] (all even)
    $expected = [2, 4, 6, 8];
    expect($pipeline->execute($input) === $expected, 'Pipeline composition failed');

    // Test DataflowEngine with generator source and sink collector
    $source = (function () {
        for ($i = 1; $i <= 5; $i++) {
            yield $i;
        }
    })();

    $collected = [];
    $sink = fn($item) => $collected[] = $item;

    DataflowEngine::run($pipeline, $source, $sink);
    $expected = [2, 4, 6, 8, 10];
    expect($collected === $expected, 'DataflowEngine run failed');

    // Test exception on empty node name
    $exceptionThrown = false;
    try {
        new TransformNode('', fn($x) => $x);
    } catch (DORAException $e) {
        $exceptionThrown = true;
    }
    expect($exceptionThrown, 'Empty node name should throw DORAException');

    echo "All unit tests passed.\n";
}

/**
 * Benchmark the pipeline with a large dataset.
 */
function runBenchmark(): void
{
    $size = 1_000_000;
    $data = range(1, $size);

    $pipeline = new Pipeline();
    $pipeline->addNode(new TransformNode('square', fn($x) => $x * $x));
    $pipeline->addNode(new FilterNode('mod3', fn($x) => $x % 3 === 0));

    $start = microtime(true);
    $result = $pipeline->execute($data);
    $duration = microtime(true) - $start;

    // Simple sanity check: first element should be 9 (3^2)
    expect($result[0] === 9, 'Benchmark sanity check failed');

    printf(
        "Benchmark completed: processed %d items in %.4f seconds (%.2f items/sec)\n",
        $size,
        $duration,
        $size / $duration
    );
}

/**
 * Entry point.
 */
if (php_sapi_name() === 'cli') {
    runTests();
    runBenchmark();
}
?>
