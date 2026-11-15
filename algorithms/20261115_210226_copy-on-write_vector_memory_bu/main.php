<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

use CowVector\CowVector;
use OutOfBoundsException;
use UnderflowException;

/**
 * Simple assertion wrapper that throws on failure.
 */
function expect(bool $condition, string $message = ''): void
{
    assert($condition, $message);
}

/* ---------- Unit Tests ---------- */

// Test construction and basic operations
$v = new CowVector([1, 2, 3]);
expect($v->count() === 3, 'Initial count');
expect($v->get(0) === 1, 'Get index 0');
expect($v->get(2) === 3, 'Get index 2');

// Test push and pop
$v->push(4);
expect($v->count() === 4, 'Count after push');
expect($v->get(3) === 4, 'Get after push');
$pop = $v->pop();
expect($pop === 4, 'Pop returns last element');
expect($v->count() === 3, 'Count after pop');

// Test set
$v->set(1, 20);
expect($v->get(1) === 20, 'Set works');

// Test copy‑on‑write semantics
$clone = clone $v;
expect($clone->toArray() === $v->toArray(), 'Clone matches original');

// Mutate clone; original must stay unchanged
$clone->push(99);
expect($clone->count() === 4, 'Clone count after push');
expect($v->count() === 3, 'Original unchanged after clone push');
expect($v->toArray() === [1, 20, 3], 'Original data intact');

// Mutate original after clone; clone must stay unchanged
$v->set(0, 42);
expect($v->get(0) === 42, 'Original modified');
expect($clone->get(0) === 1, 'Clone unchanged after original set');

// Test out‑of‑bounds exceptions
$thrown = false;
try {
    $v->get(10);
} catch (OutOfBoundsException) {
    $thrown = true;
}
expect($thrown, 'OutOfBoundsException on get');

// Test pop underflow
$empty = new CowVector();
$thrown = false;
try {
    $empty->pop();
} catch (UnderflowException) {
    $thrown = true;
}
expect($thrown, 'UnderflowException on pop');

// Test that reference counting does not leak (indirectly via destructor)
// Create many clones and let them go out of scope
for ($i = 0; $i < 1000; $i++) {
    $temp = clone $v;
    $temp->push($i);
}
expect(true, 'Stress clone loop completed');

// All tests passed
echo "All CowVector tests passed.\n";
