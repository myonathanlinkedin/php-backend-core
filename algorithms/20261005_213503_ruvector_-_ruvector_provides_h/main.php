<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

/* ---------- Unit Tests ---------- */
function test_push_pop(): void
{
    $vec = new RuVector(2);
    $vec->push(10);
    $vec->push(20);
    assert($vec->size() === 2);
    assert($vec->capacity() === 2);
    $vec->push(30); // triggers grow
    assert($vec->size() === 3);
    assert($vec->capacity() === 4);
    assert($vec->pop() === 30);
    assert($vec->pop() === 20);
    assert($vec->pop() === 10);
    try {
        $vec->pop();
        assert(false); // should not reach
    } catch (UnderflowException $e) {
        assert(true);
    }
}
test_push_pop();

function test_get_set(): void
{
    $vec = new RuVector();
    $vec->push('a');
    $vec->push('b');
    $vec->set(1, 'c');
    assert($vec->get(0) === 'a');
    assert($vec->get(1) === 'c');
    try {
        $vec->get(2);
        assert(false);
    } catch (OutOfBoundsException $e) {
        assert(true);
    }
}
test_get_set();

function test_map_filter_reduce(): void
{
    $vec = new RuVector();
    foreach (range(1, 5) as $n) {
        $vec->push($n);
    }
    $squared = $vec->map(fn($v) => $v * $v);
    assert($squared->toArray() === [1, 4, 9, 16, 25]);

    $evens = $vec->filter(fn($v) => $v % 2 === 0);
    assert($evens->toArray() === [2, 4]);

    $sum = $vec->reduce(fn($acc, $v) => $acc + $v, 0);
    assert($sum === 15);
}
test_map_filter_reduce();

function test_decide_and_snapshot(): void
{
    $vec = new RuVector();
    $vec->push('apple');
    $vec->push('banana');
    $vec->push('cherry');

    $hasBanana = $vec->decide(fn($v) => $v === 'banana');
    assert($hasBanana === true);

    $snapshot = $vec->memorySnapshot();
    assert($snapshot['size'] === 3);
    assert($snapshot['capacity'] >= 3);
    assert($snapshot['data'] === ['apple', 'banana', 'cherry']);
}
test_decide_and_snapshot();

function test_clear(): void
{
    $vec = new RuVector();
    $vec->push(1);
    $vec->push(2);
    $vec->clear();
    assert($vec->size() === 0);
    assert($vec->toArray() === []);
}
test_clear();

/* ---------- Simple Benchmark ---------- */
function benchmark_push(int $n): void
{
    $vec = new RuVector();
    $start = microtime(true);
    for ($i = 0; $i < $n; $i++) {
        $vec->push($i);
    }
    $duration = microtime(true) - $start;
    echo "Pushed $n items in " . number_format($duration, 4) . " seconds (size={$vec->size()}, capacity={$vec->capacity()})\n";
}
benchmark_push(1_000_000);

/* ---------- Entry Point ---------- */
echo "All tests passed.\n";
