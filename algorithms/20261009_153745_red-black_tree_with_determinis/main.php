<?php
declare(strict_types=1);
require_once __DIR__ . '/core.php';

// Helper to compare arrays for equality
function assertArrayEquals(array $a, array $b, string $msg = ''): void
{
    assert($a === $b, $msg ?: 'Arrays are not equal');
}

// Unit Tests
$tree = new RedBlackTree();

// Test insertion and find
$inputs = [
    10 => 'a',
    20 => 'b',
    30 => 'c',
    15 => 'd',
    25 => 'e',
    5  => 'f',
    1  => 'g',
    6  => 'h',
];

foreach ($inputs as $k => $v) {
    $tree->insert($k, $v);
    $found = $tree->find($k);
    assert($found !== null, "Key $k should be found after insertion");
    assert($found->value === $v, "Value mismatch for key $k");
    assert($tree->validate(), "Tree invalid after inserting key $k");
}

// Test inorder traversal yields sorted keys
$expectedKeys = array_keys($inputs);
sort($expectedKeys);
$inorder = $tree->inorder();
assertArrayEquals($inorder, $expectedKeys, 'Inorder traversal does not match sorted keys');

// Test duplicate key updates value (replace semantics)
$tree->insert(15, 'new-d');
$node15 = $tree->find(15);
assert($node15 !== null && $node15->value === 'new-d', 'Duplicate key should update value');
assert($tree->validate(), 'Tree invalid after updating duplicate key');

// Test empty tree validation
$emptyTree = new RedBlackTree();
assert($emptyTree->validate(), 'Empty tree should be valid');

// Stress test with random keys
$randomTree = new RedBlackTree();
$randKeys = [];
for ($i = 0; $i < 1000; $i++) {
    $k = random_int(1, 100000);
    $randKeys[$k] = $i;
    $randomTree->insert($k, $i);
    assert($randomTree->validate(), "Random tree invalid after inserting $k");
}
$sortedRandKeys = array_keys($randKeys);
sort($sortedRandKeys);
$randInorder = $randomTree->inorder();
assertArrayEquals($randInorder, $sortedRandKeys, 'Random tree inorder mismatch');

// All tests passed
echo "All Red-Black Tree tests passed.\n";
