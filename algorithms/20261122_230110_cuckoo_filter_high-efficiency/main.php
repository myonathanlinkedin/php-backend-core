<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Simple deterministic random seed for reproducibility
srand(12345);

// Unit tests
function test_basic_operations(): void
{
    $filter = new CuckooFilter(128);
    assert($filter->count() === 0);
    assert($filter->loadFactor() === 0.0);

    // Insert and lookup
    assert($filter->insert('apple') === true);
    assert($filter->contains('apple') === true);
    assert($filter->count() === 1);

    // Duplicate insert should succeed (filter allows duplicates)
    assert($filter->insert('apple') === true);
    assert($filter->count() === 2);

    // Delete one instance
    assert($filter->delete('apple') === true);
    assert($filter->contains('apple') === true); // still one left
    assert($filter->count() === 1);

    // Delete final instance
    assert($filter->delete('apple') === true);
    assert($filter->contains('apple') === false);
    assert($filter->count() === 0);

    // Delete non‑existent item
    assert($filter->delete('banana') === false);
}
test_basic_operations();

function test_bulk_insert_and_false_positive_rate(): void
{
    $capacity = 256;
    $filter = new CuckooFilter($capacity);
    $n = 200; // number of distinct items
    $items = [];

    for ($i = 0; $i < $n; $i++) {
        $item = "item_$i";
        $items[] = $item;
        assert($filter->insert($item) === true);
    }
    assert($filter->count() === $n);
    // All inserted items must be reported present
    foreach ($items as $it) {
        assert($filter->contains($it) === true);
    }

    // Test false positive rate on unseen items
    $falsePos = 0;
    $trials = 1000;
    for ($i = 0; $i < $trials; $i++) {
        $candidate = "unknown_$i";
        if ($filter->contains($candidate)) {
            $falsePos++;
        }
    }
    $rate = $falsePos / $trials;
    // Expected false positive rate for 1‑byte fingerprint ~ 1/256 ≈ 0.004
    // Allow generous margin
    assert($rate < 0.02);
}
test_bulk_insert_and_false_positive_rate();

function test_capacity_limits(): void
{
    $filter = new CuckooFilter(32, 2, 1, 100); // small bucket size to force failures
    $inserted = 0;
    for ($i = 0; $i < 200; $i++) {
        $ok = $filter->insert("key_$i");
        if ($ok) {
            $inserted++;
        } else {
            break;
        }
    }
    // Should have stopped before reaching 200 inserts
    assert($inserted < 200);
    // Ensure filter reports false for a definitely absent key
    assert($filter->contains('definitely_not_present') === false);
}
test_capacity_limits();

function test_clear_functionality(): void
{
    $filter = new CuckooFilter(64);
    $filter->insert('alpha');
    $filter->insert('beta');
    assert($filter->count() === 2);
    $filter->clear();
    assert($filter->count() === 0);
    assert($filter->contains('alpha') === false);
    assert($filter->contains('beta') === false);
}
test_clear_functionality();

echo "All tests passed.\n";
