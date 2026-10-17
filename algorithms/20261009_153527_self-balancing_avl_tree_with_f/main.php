<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

// Enable strict assertions
assert_options(ASSERT_ACTIVE,   true);
assert_options(ASSERT_BAIL,     true);
assert_options(ASSERT_WARNING, true);
assert_options(ASSERT_CALLBACK, function (int $file, int $line, string $code, string $desc = null) {
    echo "Assertion failed at $file:$line: $code\n";
    if ($desc !== null) {
        echo "Description: $desc\n";
    }
    exit(1);
});

// -----------------------------------------------------------------
// Unit Tests
// -----------------------------------------------------------------

$tree = new AVLTree();

// Test 1: Insert a known sequence and verify inorder sorting
$seq = [30, 20, 40, 10, 25, 35, 50, 5, 15, 27];
foreach ($seq as $i => $key) {
    $tree->insert($key, "val{$key}");
    // After each insertion, AVL property must hold
    assert($tree->isValidAVL(), "AVL invariant broken after inserting {$key}");
}
$expectedInorder = [5,10,15,20,25,27,30,35,40,50];
assert($tree->inorder() === $expectedInorder, 'Inorder traversal mismatch');

// Test 2: Find existing and non‑existing keys
foreach ($expectedInorder as $k) {
    $val = $tree->find($k);
    assert($val === "val{$k}", "Find failed for key {$k}");
}
assert($tree->find(999) === null, 'Find should return null for missing key');

// Test 3: Delete leaf, node with one child, node with two children
$tree->delete(5);   // leaf
assert(!$tree->find(5));
assert($tree->isValidAVL());

$tree->delete(40); // node with two children
assert(!$tree->find(40));
assert($tree->isValidAVL());

$tree->delete(20); // node with one child after previous deletions
assert(!$tree->find(20));
assert($tree->isValidAVL());

// Verify remaining inorder sequence
$expectedAfterDeletes = [10,15,25,27,30,35,50];
assert($tree->inorder() === $expectedAfterDeletes, 'Inorder after deletions mismatch');

// Test 4: Re‑insert duplicate keys (should replace value, not change structure)
$tree->insert(30, 'new30');
assert($tree->find(30) === 'new30');
assert($tree->isValidAVL());

// Test 5: Stress test with many random insertions/deletions
$randomTree = new AVLTree();
$keys = range(1, 1000);
shuffle($keys);
foreach ($keys as $k) {
    $randomTree->insert($k, $k);
    assert($randomTree->isValidAVL(), "AVL broken during bulk insert of {$k}");
}
shuffle($keys);
foreach (array_slice($keys, 0, 500) as $k) {
    $randomTree->delete($k);
    assert($randomTree->isValidAVL(), "AVL broken during bulk delete of {$k}");
}
$remaining = $randomTree->inorder();
sort($remaining);
assert($remaining === $randomTree->inorder(), 'Inorder should be sorted after bulk ops');

// Test 6: Traversal orders sanity check
$pre  = $tree->preorder();
$post = $tree->postorder();
assert(is_array($pre) && count($pre) === count($expectedAfterDeletes), 'Preorder length mismatch');
assert(is_array($post) && count($post) === count($expectedAfterDeletes), 'Postorder length mismatch');

// All tests passed
echo "All AVL Tree unit tests passed.\n";
