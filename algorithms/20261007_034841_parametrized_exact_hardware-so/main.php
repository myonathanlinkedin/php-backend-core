<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Helper to compare two associative arrays irrespective of order
function assertArraysEqual(array $expected, array $actual, string $message = ''): void
{
    ksort($expected);
    ksort($actual);
    assert($expected === $actual, $message);
}

// Test 1: Single task fits only in hardware
$tasks = [new Task('A', 5, 10, 100)];
$solver = new PartitionSolver();
$result = $solver->solve($tasks, 5, 0);
assert($result->totalBenefit === 100, 'Test1 benefit');
assert($result->usedHardware === 5, 'Test1 hardware usage');
assert($result->usedSoftware === 0, 'Test1 software usage');
assertArraysEqual(['A' => 'HW'], $result->assignments, 'Test1 assignments');

// Test 2: Single task fits only in software
$tasks = [new Task('B', 8, 3, 70)];
$result = $solver->solve($tasks, 0, 3);
assert($result->totalBenefit === 70, 'Test2 benefit');
assert($result->usedHardware === 0, 'Test2 hardware usage');
assert($result->usedSoftware === 3, 'Test2 software usage');
assertArraysEqual(['B' => 'SW'], $result->assignments, 'Test2 assignments');

// Test 3: Two tasks, budget forces choice
$tasks = [
    new Task('C', 4, 2, 60),
    new Task('D', 6, 1, 80)
];
$result = $solver->solve($tasks, 5, 2);
assert($result->totalBenefit === 80, 'Test3 benefit');
assert($result->usedHardware === 6 || $result->usedSoftware === 1, 'Test3 usage');
assertArraysEqual(['C' => 'NONE', 'D' => 'HW'], $result->assignments, 'Test3 assignments');

// Test 4: All tasks fit, maximize benefit
$tasks = [
    new Task('E', 2, 3, 40),
    new Task('F', 3, 2, 50),
    new Task('G', 1, 1, 30)
];
$result = $solver->solve($tasks, 5, 5);
assert($result->totalBenefit === 120, 'Test4 benefit');
assert($result->usedHardware === 5, 'Test4 hardware usage');
assert($result->usedSoftware === 5, 'Test4 software usage');
assertArraysEqual(['E' => 'HW', 'F' => 'SW', 'G' => 'HW'], $result->assignments, 'Test4 assignments');

// Test 5: No tasks can be placed
$tasks = [new Task('H', 10, 10, 200)];
$result = $solver->solve($tasks, 5, 5);
assert($result->totalBenefit === 0, 'Test5 benefit');
assert($result->usedHardware === 0, 'Test5 hardware usage');
assert($result->usedSoftware === 0, 'Test5 software usage');
assertArraysEqual(['H' => 'NONE'], $result->assignments, 'Test5 assignments');

// Test 6: Larger random set (deterministic)
$tasks = [
    new Task('I', 3, 4, 25),
    new Task('J', 2, 5, 30),
    new Task('K', 5, 2, 45),
    new Task('L', 1, 3, 20)
];
$result = $solver->solve($tasks, 7, 7);
assert($result->totalBenefit === 95, 'Test6 benefit');
assert($result->usedHardware <= 7 && $result->usedSoftware <= 7, 'Test6 budget respect');
assertArraysEqual(['I' => 'HW', 'J' => 'SW', 'K' => 'HW', 'L' => 'NONE'], $result->assignments, 'Test6 assignments');

// Simple benchmark (optional)
$largeTasks = [];
for ($i = 0; $i < 30; $i++) {
    $largeTasks[] = new Task('T' . $i, rand(1, 5), rand(1, 5), rand(10, 100));
}
$start = microtime(true);
$solver->solve($largeTasks, 50, 50);
$elapsed = microtime(true) - $start;
assert($elapsed < 2.0, 'Benchmark within 2 seconds');

// Entry point (no output needed)
echo "All tests passed.\n";
