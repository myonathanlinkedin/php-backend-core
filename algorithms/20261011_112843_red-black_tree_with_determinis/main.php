<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Enable strict assertions (throws AssertionError on failure)
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_EXCEPTION, 1);

function runTests(): void
{
    $tree = new RedBlackTree();

    // Test insertion and validation
    $keys = [10, 20, 30, 15, 25, 5, 1, 40, 35, 45];
    foreach ($keys as $i => $k) {
        $tree->insert($k, "val{$k}");
        assert($tree->isValid(), "Tree invalid after inserting {$k}");
        $sorted = $tree->inorderKeys();
        $expected = $keys;
        sort($expected);
        assert($sorted === $expected, "Inorder mismatch after inserting {$k}");
    }

    // Test find operation
    foreach ($keys as $k) {
        $val = $tree->find($k);
        assert($val === "val{$k}", "Find failed for key {$k}");
    }
    assert($tree->find(999) === null, "Find should return null for missing key");

    // Edge case: inserting duplicate key updates value
    $tree->insert(20, 'new20');
    assert($tree->find(20) === 'new20', "Duplicate key update failed");
    assert($tree->isValid(), "Tree invalid after duplicate key update");

    // Stress test: insert sequential numbers 1..100
    $bigTree = new RedBlackTree();
    for ($i = 1; $i <= 100; $i++) {
        $bigTree->insert($i, $i);
        assert($bigTree->isValid(), "Big tree invalid at {$i}");
    }
    // Verify black-height consistency
    $blackHeight = null;
    $reflection = new ReflectionClass($bigTree);
    $prop = $reflection->getProperty('root');
    $prop->setAccessible(true);
    $rootNode = $prop->getValue($bigTree);
    $method = $reflection->getMethod('validateNode');
    $method->setAccessible(true);
    $valid = $method->invoke($bigTree, $rootNode, 0, $blackHeight);
    assert($valid, "Black-height validation failed for big tree");
    assert(is_int($blackHeight) && $blackHeight > 0, "Invalid black-height computed");
}

runTests();

echo "All Red-Black Tree tests passed.\n";
