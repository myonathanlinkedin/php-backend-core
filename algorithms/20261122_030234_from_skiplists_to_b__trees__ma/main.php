<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Simple assertion helper to provide readable messages.
function expect($condition, string $message = ''): void
{
    assert($condition, $message);
}

// Unit Tests
function testInsertionAndSearch(): void
{
    $tree = new BPlusTree(4);
    $pairs = [
        10 => 'a',
        20 => 'b',
        5  => 'c',
        15 => 'd',
        25 => 'e',
        3  => 'f',
        8  => 'g',
    ];
    foreach ($pairs as $k => $v) {
        $tree->insert($k, $v);
    }

    foreach ($pairs as $k => $v) {
        $found = $tree->search($k);
        expect($found === $v, "Search failed for key $k");
    }

    // Overwrite test
    $tree->insert(10, 'z');
    expect($tree->search(10) === 'z', 'Overwrite failed for key 10');
}

function testRangeQuery(): void
{
    $tree = new BPlusTree(4);
    for ($i = 1; $i <= 20; $i++) {
        $tree->insert($i, $i * 10);
    }

    $range = $tree->range(5, 12);
    $expected = [];
    for ($i = 5; $i <= 12; $i++) {
        $expected[] = $i * 10;
    }
    expect($range === $expected, 'Range query 5-12 failed');

    $rangeAll = $tree->range(1, 20);
    expect(count($rangeAll) === 20, 'Full range size mismatch');

    $empty = $tree->range(30, 40);
    expect($empty === [], 'Empty range should return empty array');
}

function testDeletion(): void
{
    $tree = new BPlusTree(4);
    $keys = [50, 20, 70, 10, 30, 60, 80];
    foreach ($keys as $k) {
        $tree->insert($k, $k);
    }

    $tree->delete(30);
    expect($tree->search(30) === null, 'Deletion of 30 failed');

    $tree->delete(10);
    expect($tree->search(10) === null, 'Deletion of 10 failed');

    // Delete non‑existent key should not affect others.
    $tree->delete(999);
    foreach ([20, 50, 60, 70, 80] as $k) {
        expect($tree->search($k) === $k, "Key $k missing after unrelated deletion");
    }
}

// Run tests
testInsertionAndSearch();
testRangeQuery();
testDeletion();

echo "All tests passed.\n";
