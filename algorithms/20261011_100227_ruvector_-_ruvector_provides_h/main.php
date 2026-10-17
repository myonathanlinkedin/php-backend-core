<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Helper to report test failures with context.
function testAssert(bool $condition, string $message = ''): void
{
    if (!$condition) {
        $msg = $message !== '' ? $message : 'Assertion failed';
        throw new RuntimeException($msg);
    }
}

// 1. Construction and basic properties
$vec = new RuVector(2);
testAssert($vec->size() === 0, 'Initial size must be 0');
testAssert($vec->capacity() === 2, 'Initial capacity must respect constructor');

// 2. Push and automatic growth
$vec->push(10);
$vec->push(20);
testAssert($vec->size() === 2, 'Size after two pushes');
testAssert($vec->capacity() === 2, 'Capacity unchanged before growth');
$vec->push(30); // triggers growth
testAssert($vec->size() === 3, 'Size after growth push');
testAssert($vec->capacity() === 4, 'Capacity should double to 4');

// 3. Random access
testAssert($vec->get(0) === 10, 'Get index 0');
testAssert($vec->get(1) === 20, 'Get index 1');
testAssert($vec->get(2) === 30, 'Get index 2');

// 4. Set operation
$vec->set(1, 99);
testAssert($vec->get(1) === 99, 'Set index 1');

// 5. Pop operation
$last = $vec->pop();
testAssert($last === 30, 'Pop returns last element');
testAssert($vec->size() === 2, 'Size after pop');

// 6. Bounds checking
$exceptionCaught = false;
try {
    $vec->get(5);
} catch (OutOfRangeException) {
    $exceptionCaught = true;
}
testAssert($exceptionCaught, 'OutOfRangeException on get out of bounds');

$exceptionCaught = false;
try {
    $vec->set(-1, 0);
} catch (OutOfRangeException) {
    $exceptionCaught = true;
}
testAssert($exceptionCaught, 'OutOfRangeException on set negative index');

// 7. Pop until empty and underflow
$vec->pop(); // removes 99
$vec->pop(); // removes 10
testAssert($vec->size() === 0, 'Vector empty after popping all');
$exceptionCaught = false;
try {
    $vec->pop();
} catch (UnderflowException) {
    $exceptionCaught = true;
}
testAssert($exceptionCaught, 'UnderflowException on pop from empty vector');

// 8. Clear method
$vec->push('a');
$vec->push('b');
$vec->clear();
testAssert($vec->size() === 0, 'Size after clear');
testAssert($vec->capacity() >= 2, 'Capacity unchanged after clear');

// 9. toArray correctness
$vec->push(1);
$vec->push(2);
$vec->push(3);
$array = $vec->toArray();
testAssert($array === [0 => 1, 1 => 2, 2 => 3], 'toArray returns exact sequence');

// 10. Iterator behavior
$collected = [];
foreach ($vec as $idx => $val) {
    $collected[$idx] = $val;
}
testAssert($collected === $array, 'Iterator yields same elements as toArray');

// 11. Countable interface
testAssert(count($vec) === $vec->size(), 'Countable count matches size');

// 12. Stress test: push many elements to verify multiple growth steps
$bigVec = new RuVector(1);
for ($i = 0; $i < 1000; $i++) {
    $bigVec->push($i);
}
testAssert($bigVec->size() === 1000, 'Large vector size after 1000 pushes');
testAssert($bigVec->get(999) === 999, 'Last element correct after many pushes');

// 13. Verify capacity is a power of two (property of doubling strategy)
$capacity = $bigVec->capacity();
$isPowerOfTwo = ($capacity & ($capacity - 1)) === 0;
testAssert($isPowerOfTwo, 'Capacity remains power of two after growth');

// All tests passed
echo "All RuVector unit tests passed.\n";
