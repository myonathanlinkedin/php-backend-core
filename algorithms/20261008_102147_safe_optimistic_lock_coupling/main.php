<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Simple assertion helper.
function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
{
    assert($expected === $actual, $message ?: "Expected " . var_export($expected, true) . " but got " . var_export($actual, true));
}

// Unit tests for OptimisticLinkedList.
function runTests(): void
{
    $list = new OptimisticLinkedList();

    // Test empty get.
    assertEquals(null, $list->get(10), 'Get on empty list should return null');

    // Insert single element.
    $list->set(10, 'ten');
    assertEquals(['10' => 'ten'], $list->toArray(), 'List after single insert');
    assertEquals('ten', $list->get(10), 'Retrieve existing key');

    // Update existing key.
    $list->set(10, 'TEN');
    assertEquals(['10' => 'TEN'], $list->toArray(), 'Update existing key');
    assertEquals('TEN', $list->get(10), 'Retrieve updated value');

    // Insert multiple keys in unsorted order, list should stay sorted.
    $list->set(5, 'five');
    $list->set(20, 'twenty');
    $list->set(15, 'fifteen');
    $expected = [
        5 => 'five',
        10 => 'TEN',
        15 => 'fifteen',
        20 => 'twenty',
    ];
    assertEquals($expected, $list->toArray(), 'List after multiple inserts');

    // Delete non-existing key.
    assertEquals(false, $list->delete(99), 'Delete non-existing key returns false');

    // Delete head-adjacent key.
    assertEquals(true, $list->delete(5), 'Delete existing key returns true');
    unset($expected[5]);
    assertEquals($expected, $list->toArray(), 'List after deleting key 5');

    // Delete middle key.
    assertEquals(true, $list->delete(15), 'Delete middle key');
    unset($expected[15]);
    assertEquals($expected, $list->toArray(), 'List after deleting key 15');

    // Delete last key.
    assertEquals(true, $list->delete(20), 'Delete last key');
    unset($expected[20]);
    assertEquals($expected, $list->toArray(), 'List after deleting key 20');

    // Ensure remaining key works.
    assertEquals('TEN', $list->get(10), 'Remaining key still accessible');

    // Stress test: repeated insert/delete cycles.
    for ($i = 0; $i < 100; $i++) {
        $list->set($i, $i * 2);
    }
    for ($i = 0; $i < 100; $i++) {
        assertEquals($i * 2, $list->get($i), "Value after bulk insert for key $i");
    }
    for ($i = 0; $i < 100; $i += 2) {
        $list->delete($i);
    }
    for ($i = 0; $i < 100; $i++) {
        $expectedValue = ($i % 2 === 0) ? null : $i * 2;
        assertEquals($expectedValue, $list->get($i), "Value after selective delete for key $i");
    }

    // Final structure sanity check.
    $finalArray = $list->toArray();
    foreach ($finalArray as $k => $v) {
        assertEquals($k * 2, $v, "Final array consistency for key $k");
    }

    echo "All tests passed.\n";
}

// Run the test suite.
runTests();
