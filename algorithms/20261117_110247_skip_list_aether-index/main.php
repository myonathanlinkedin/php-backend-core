<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// deterministic randomness for reproducible tests
mt_srand(0);

// enable strict assertions
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_EXCEPTION, 1);

// Test 1: basic insert and search
$sl = new SkipList();
$sl->insert(10, 'ten');
$sl->insert(20, 'twenty');
$sl->insert(5, 'five');
assert($sl->search(10) === 'ten');
assert($sl->search(5) === 'five');
assert($sl->search(20) === 'twenty');
assert($sl->search(15) === null);

// Test 2: update existing key
$sl->insert(10, 'TEN');
assert($sl->search(10) === 'TEN');

// Test 3: delete existing and non‑existing keys
assert($sl->delete(5) === true);
assert($sl->search(5) === null);
assert($sl->delete(5) === false);
assert($sl->delete(999) === false);

// Test 4: range query
$sl->insert(1, 'one');
$sl->insert(3, 'three');
$sl->insert(7, 'seven');
$sl->insert(9, 'nine');
$range = $sl->range(3, 8);
$expected = [3 => 'three', 7 => 'seven'];
assert($range === $expected);

// Test 5: edge cases – minimum and maximum integer keys
$sl->insert(PHP_INT_MIN, 'min');
$sl->insert(PHP_INT_MAX, 'max');
assert($sl->search(PHP_INT_MIN) === 'min');
assert($sl->search(PHP_INT_MAX) === 'max');
$rangeAll = $sl->range(PHP_INT_MIN, PHP_INT_MAX);
assert(isset($rangeAll[PHP_INT_MIN]) && $rangeAll[PHP_INT_MIN] === 'min');
assert(isset($rangeAll[PHP_INT_MAX]) && $rangeAll[PHP_INT_MAX] === 'max');

// Test 6: internal level never exceeds maxLevel
assert($sl->getCurrentLevel() <= 16);

// All assertions passed – the skip list implementation is verified.
