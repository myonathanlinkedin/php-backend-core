<?php
declare(strict_types=1);
require_once 'core.php';

function testLinkedList(): void {
    $list = new LinkedList();
    assert($list->size() === 0);
    $list->add(1);
    $list->add(2);
    $list->add(3);
    assert($list->size() === 3);
    assert($list->toArray() === [1,2,3]);

    $node = $list->find(2);
    assert($node !== null && $node->value === 2);

    $removed = $list->remove(2);
    assert($removed === true);
    assert($list->size() === 2);
    assert($list->toArray() === [1,3]);

    $removed = $list->remove(4);
    assert($removed === false);

    $list->remove(1);
    $list->remove(3);
    assert($list->size() === 0);
    assert($list->toArray() === []);
}

testLinkedList();
echo "All tests passed.\n";
