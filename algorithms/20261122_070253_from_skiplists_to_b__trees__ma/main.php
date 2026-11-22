<?php
declare(strict_types=1);
require_once 'core.php';

$tree = new BPlusTree(4);
$tree->insert(10, 100);
$tree->insert(20, 200);
$tree->insert(5, 50);
$tree->insert(15, 150);
$tree->insert(25, 250);
$tree->insert(30, 300);
$tree->insert(1, 10);
$tree->insert(3, 30);
$tree->insert(7, 70);
$tree->insert(9, 90);

assert($tree->search(10) === 100);
assert($tree->search(15) === 150);
assert($tree->search(30) === 300);
assert($tree->search(2) === null);

$range = $tree->rangeSearch(5, 20);
$expected = [[5,50],[7,70],[9,90],[10,100],[15,150]];
assert($range === $expected);

$tree->insert(15, 155);
assert($tree->search(15) === 155);

echo "All tests passed.\n";
