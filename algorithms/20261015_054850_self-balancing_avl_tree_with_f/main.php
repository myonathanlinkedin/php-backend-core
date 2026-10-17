<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// enable strict assertions
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);
assert_options(ASSERT_EXCEPTION, 1);

// Helper to verify AVL balance property recursively
function assertBalanced(?AVLNode $node): void
{
    if ($node === null) {
        return;
    }
    $leftHeight  = $node->left ? $node->left->height : 0;
    $rightHeight = $node->right ? $node->right->height : 0;
    $balance = $leftHeight - $rightHeight;
    assert(abs($balance) <= 1, "Balance factor out of range at key {$node->key}");
    assertBalanced($node->left);
    assertBalanced($node->right);
}

// Test 1: Insert sequence causing all rotation types
$tree = new AVLTree();
$keys = [30, 20, 40, 10, 25, 35, 50, 5, 15, 27];
foreach ($keys as $k) {
    $tree->insert($k);
}
$expectedInorder = [5, 10, 15, 20, 25, 27, 30, 35, 40, 50];
assert($tree->inorder() === $expectedInorder, 'Inorder after insertions');
assertBalanced($tree->getRoot());

// Test 2: Search existing and non‑existing keys
assert($tree->find(25)?->key === 25, 'Find existing key');
assert($tree->find(99) === null, 'Find non‑existing key');

// Test 3: Delete leaf node
$tree->delete(5);
$expectedInorder = [10, 15, 20, 25, 27, 30, 35, 40, 50];
assert($tree->inorder() === $expectedInorder, 'Inorder after deleting leaf');
assertBalanced($tree->getRoot());

// Test 4: Delete node with one child
$tree->delete(35);
$expectedInorder = [10, 15, 20, 25, 27, 30, 40, 50];
assert($tree->inorder() === $expectedInorder, 'Inorder after deleting node with one child');
assertBalanced($tree->getRoot());

// Test 5: Delete node with two children
$tree->delete(20);
$expectedInorder = [10, 15, 25, 27, 30, 40, 50];
assert($tree->inorder() === $expectedInorder, 'Inorder after deleting node with two children');
assertBalanced($tree->getRoot());

// Test 6: Insert duplicate key (should be ignored)
$tree->insert(30);
assert(count($tree->inorder()) === 7, 'Duplicate insertion does not change size');

// Test 7: Stress test random insert/delete while maintaining balance
$stress = new AVLTree();
$values = range(1, 100);
shuffle($values);
foreach ($values as $v) {
    $stress->insert($v);
}
assertBalanced($stress->getRoot());

// Random deletions
shuffle($values);
$toDelete = array_slice($values, 0, 30);
foreach ($toDelete as $v) {
    $stress->delete($v);
}
assertBalanced($stress->getRoot());

// Final sanity: inorder must be sorted and contain expected count
$finalInorder = $stress->inorder();
$sorted = $finalInorder;
sort($sorted);
assert($finalInorder === $sorted, 'Final inorder sorted');
assert(count($finalInorder) === 70, 'Final node count after deletions');

echo "All AVLTree tests passed.\n";
