<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Simple assertion helper to provide clearer messages.
function expect(bool $condition, string $message = ''): void
{
    assert($condition, $message);
}

// Unit Tests ---------------------------------------------------------

// Test construction and basic operations.
$vec = new CowVector([1, 2, 3]);
expect($vec->length() === 3, 'Initial length');
expect($vec->get(0) === 1, 'Get index 0');
expect($vec->get(2) === 3, 'Get index 2');

// Test push and pop.
$vec->push(4);
expect($vec->length() === 4, 'Length after push');
expect($vec->get(3) === 4, 'Value after push');
$pop = $vec->pop();
expect($pop === 4, 'Pop returns last value');
expect($vec->length() === 3, 'Length after pop');

// Test set.
$vec->set(1, 20);
expect($vec->get(1) === 20, 'Set updates value');

// Test copy‑on‑write semantics.
$copy = $vec->copy();               // Shared buffer.
expect($vec->toArray() === $copy->toArray(), 'Copy shares data');
expect($vec->length() === $copy->length(), 'Copy length matches');

$copy->push(99);                    // Should trigger detach.
expect($copy->length() === 4, 'Copy length after push');
expect($vec->length() === 3, 'Original unchanged after copy push');
expect($vec->toArray() !== $copy->toArray(), 'Buffers are distinct after write');

// Ensure original buffer reference count dropped to 1.
$originalRefCount = (new ReflectionObject($vec))->getProperty('buffer');
$originalRefCount->setAccessible(true);
$bufferObj = $originalRefCount->getValue($vec);
expect($bufferObj->getRefCount() === 1, 'Original buffer refCount is 1');

// Test out‑of‑bounds exceptions.
$exceptionThrown = false;
try {
    $vec->get(10);
} catch (OutOfBoundsException $e) {
    $exceptionThrown = true;
}
expect($exceptionThrown, 'OutOfBoundsException on get');

// Test pop on empty vector.
$empty = new CowVector();
$exceptionThrown = false;
try {
    $empty->pop();
} catch (UnderflowException $e) {
    $exceptionThrown = true;
}
expect($exceptionThrown, 'UnderflowException on pop');

// Benchmark (very light, deterministic) --------------------------------
function benchmarkPush(int $n): float
{
    $v = new CowVector();
    $start = microtime(true);
    for ($i = 0; $i < $n; $i++) {
        $v->push($i);
    }
    return microtime(true) - $start;
}

// Run a tiny benchmark to ensure deterministic execution.
$time = benchmarkPush(1000);
expect($time >= 0.0, 'Benchmark runs without error');

// All tests passed message (optional, not required by spec).
echo "All unit tests passed.\n";
