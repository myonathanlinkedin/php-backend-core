<?php
declare(strict_types=1);
require_once __DIR__ . '/core.php';

// Helper for clearer assertions
function assert_eq(mixed $a, mixed $b, string $msg = ''): void
{
    assert($a === $b, $msg);
}

// Test 1: Basic push/pop behavior
$v = new CowVector(2);
assert_eq($v->size(), 0, 'initial size 0');
$v->push(10);
$v->push(20);
assert_eq($v->size(), 2, 'size after two pushes');
assert_eq($v->capacity(), 2, 'capacity unchanged');
$v->push(30); // triggers resize
assert_eq($v->size(), 3, 'size after resize push');
assert_eq($v->capacity(), 4, 'capacity doubled');
assert_eq($v->get(0), 10, 'value at 0');
assert_eq($v->get(2), 30, 'value at 2');
$val = $v->pop();
assert_eq($val, 30, 'pop returns last');
assert_eq($v->size(), 2, 'size after pop');

// Test 2: Copy-on-Write semantics
$a = new CowVector();
$a->push('x');
$a->push('y');
$b = clone $a; // shared buffer
assert_eq($a->size(), $b->size(), 'clone size equal');
assert_eq($a->getRefCount(), 2, 'buffer shared count 2');
$b->set(0, 'z'); // should detach
assert_eq($a->get(0), 'x', 'original unchanged after set on clone');
assert_eq($b->get(0), 'z', 'clone sees new value');
assert_eq($a->getRefCount(), 1, 'original buffer refcount 1');
assert_eq($b->getRefCount(), 1, 'clone buffer refcount 1');

// Test 3: Further modifications on clone do not affect original
$b->push('w');
assert_eq($b->size(), 3, 'clone size after push');
assert_eq($a->size(), 2, 'original size unchanged');
assert_eq($b->get(2), 'w', 'clone new element');

// Test 4: Out-of-bounds handling
$exceptionCaught = false;
try {
    $a->get(5);
} catch (OutOfBoundsException) {
    $exceptionCaught = true;
}
assert($exceptionCaught, 'get out-of-bounds throws');

// Test 5: Pop from empty vector throws
$empty = new CowVector();
$exceptionCaught = false;
try {
    $empty->pop();
} catch (UnderflowException) {
    $exceptionCaught = true;
}
assert($exceptionCaught, 'pop empty throws');

// Test 6: Capacity growth multiple times
$c = new CowVector(1);
for ($i = 0; $i < 10; $i++) {
    $c->push($i);
}
assert_eq($c->size(), 10, 'size after 10 pushes');
assert($c->capacity() >= 10, 'capacity grew to accommodate 10 elements');

// All tests passed
echo "All tests passed.\n";
