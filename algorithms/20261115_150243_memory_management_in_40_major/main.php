<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

/**
 * Helper to compare floating point numbers within a tolerance.
 */
function assertFloatEquals(float $expected, float $actual, float $epsilon = 1e-6, string $message = ''): void
{
    $diff = abs($expected - $actual);
    assert($diff <= $epsilon, $message ?: "Expected $expected, got $actual (diff $diff > $epsilon)");
}

/* ---------- Unit Tests ---------- */

// Test 1: Empty survey
$survey = new MemorySurvey();
assert($survey->getProjectCount() === 0, 'Empty survey should have 0 projects');
assert($survey->getTotalAllocations() === 0, 'Empty survey total allocations should be 0');
assert($survey->getTotalFrees() === 0, 'Empty survey total frees should be 0');
assertFloatEquals(0.0, $survey->getOverallFreeToAllocRatio(), 1e-9, 'Empty survey ratio should be 0.0');
assertFloatEquals(0.0, $survey->getAveragePeakMemoryKB(), 1e-9, 'Empty survey average peak memory should be 0.0');
assert($survey->getMostCommonAllocationFunction() === null, 'Empty survey most common function should be null');

// Test 2: Single project
$projA = new ProjectMemoryStats(
    'projA',
    allocationCount: 200,
    freeCount: 180,
    peakMemoryKB: 1024,
    allocationFunctions: ['malloc' => 150, 'calloc' => 30, 'realloc' => 20]
);
$survey->addProject($projA);
assert($survey->getProjectCount() === 1, 'Survey should have 1 project after addition');
assert($survey->getTotalAllocations() === 200, 'Total allocations mismatch');
assert($survey->getTotalFrees() === 180, 'Total frees mismatch');
assertFloatEquals(0.9, $survey->getOverallFreeToAllocRatio(), 1e-9, 'Free-to-alloc ratio mismatch');
assertFloatEquals(1024.0, $survey->getAveragePeakMemoryKB(), 1e-9, 'Average peak memory mismatch');
assert($survey->getMostCommonAllocationFunction() === 'malloc', 'Most common function should be malloc');

// Test 3: Multiple projects with tie on most common function
$projB = new ProjectMemoryStats(
    'projB',
    allocationCount: 300,
    freeCount: 250,
    peakMemoryKB: 2048,
    allocationFunctions: ['malloc' => 120, 'calloc' => 150, 'new' => 30]
);
$projC = new ProjectMemoryStats(
    'projC',
    allocationCount: 100,
    freeCount: 90,
    peakMemoryKB: 512,
    allocationFunctions: ['malloc' => 50, 'calloc' => 50]
);
$survey->addProject($projB);
$survey->addProject($projC);
assert($survey->getProjectCount() === 3, 'Survey should have 3 projects now');
assert($survey->getTotalAllocations() === 600, 'Total allocations after three projects');
assert($survey->getTotalFrees() === 520, 'Total frees after three projects');
assertFloatEquals(520 / 600, $survey->getOverallFreeToAllocRatio(), 1e-9, 'Overall ratio after three projects');
assertFloatEquals((1024 + 2048 + 512) / 3, $survey->getAveragePeakMemoryKB(), 1e-9, 'Average peak memory after three projects');

// Determine most common allocation function:
// malloc total = 150 + 120 + 50 = 320
// calloc total = 30 + 150 + 50 = 230
// realloc total = 20
// new total = 30
// So malloc wins.
assert($survey->getMostCommonAllocationFunction() === 'malloc', 'Most common function should still be malloc');

// Test 4: Tie-breaking (lexicographically smallest)
$projD = new ProjectMemoryStats(
    'projD',
    allocationCount: 50,
    freeCount: 45,
    peakMemoryKB: 256,
    allocationFunctions: ['calloc' => 100] // adds 100 to calloc, making calloc total = 330 (ties with malloc)
);
$survey->addProject($projD);
// Now malloc = 320, calloc = 330 -> calloc wins.
assert($survey->getMostCommonAllocationFunction() === 'calloc', 'After tie, calloc should be most common');

// Test 5: Overwrite existing project
$projA2 = new ProjectMemoryStats(
    'projA',
    allocationCount: 400,
    freeCount: 380,
    peakMemoryKB: 1500,
    allocationFunctions: ['malloc' => 300, 'calloc' => 100]
);
$survey->addProject($projA2);
assert($survey->getProjectCount() === 4, 'Project count unchanged after overwrite');
assert($survey->getTotalAllocations() === (400 + 300 + 100 + 50), 'Total allocations after overwrite');
assertFloatEquals(
    ($survey->getTotalFrees() / $survey->getTotalAllocations()),
    $survey->getOverallFreeToAllocRatio(),
    1e-9,
    'Ratio recomputed after overwrite'
);

// Test 6: Retrieval of a specific project
$retrieved = $survey->getProject('projA');
assert($retrieved !== null, 'Retrieved project A should not be null');
assert($retrieved->getPeakMemoryKB() === 1500, 'Retrieved project A peak memory mismatch');

// All assertions passed if execution reaches this point without error.
echo "All unit tests passed.\n";
