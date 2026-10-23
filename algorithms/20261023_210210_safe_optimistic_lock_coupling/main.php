<?php

require_once 'core.php';

$list = new OptimisticLinkedList();

// Test insertion of unique keys
assert($list->insert(10, 'ten') === true);
assert($list->insert(5, 'five') === true);
assert($list->insert(20, 'twenty') === true);

// Test duplicate insertion
assert($list->insert(10, 'TEN') === false);

// Test contains
assert($list->contains(10) === true);
assert($list->contains(5) === true);
assert($list->contains(20) === true);
assert($list->contains(15) === false);

// Test deletion of existing key
assert($list->delete(5) === true);
assert($list->contains(5) === false);

// Test deletion of non-existent key
assert($list->delete(5) === false);

// Test order of elements
$expected = [
    ['key' => 10, 'value' => 'ten'],
    ['key' => 20, 'value' => 'twenty'],
];
assert($list->toArray() === $expected);

// Test find method
$node = $list->find(20);
assert($node !== null && $node->key === 20 && $node->value === 'twenty');
$node = $list->find(15);
assert($node === null);

// Stress test with multiple operations
for ($i = 0; $i < 100; $i++) {
    $key = $i * 2;
    assert($list->insert($key, "num{$key}") === true);
}
for ($i = 0; $i < 100; $i++) {
    $key = $i * 2;
    assert($list->contains($key) === true);
}
for ($i = 0; $i < 100; $i++) {
    $key = $i * 2;
    assert($list->delete($key) === true);
}
for ($i = 0; $i < 100; $i++) {
    $key = $i * 2;
    assert($list->contains($key) === false);
}

// Final list should contain only the initial keys 10 and 20
$final = $list->toArray();
assert(count($final) === 2);
assert($final[0]['key'] === 10);
assert($final[1]['key'] === 20);

echo "All tests passed.\n";
?>
