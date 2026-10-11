<?php
declare(strict_types=1);

require_once 'core.php';

function testInsertFindDelete(): void {
    $list = new SafeOptimisticLockCouplingList();
    assert($list->insert(10, 'ten') === true);
    assert($list->insert(5, 'five') === true);
    assert($list->insert(15, 'fifteen') === true);
    assert($list->insert(10, 'duplicate') === false);

    $node = $list->find(5);
    assert($node !== null && $node->value === 'five');
    $node = $list->find(15);
    assert($node !== null && $node->value === 'fifteen');
    $node = $list->find(20);
    assert($node === null);

    assert($list->delete(5) === true);
    assert($list->delete(5) === false);
    assert($list->delete(20) === false);

    $arr = $list->toArray();
    assert(count($arr) === 2);
    assert($arr[0]['key'] === 10);
    assert($arr[1]['key'] === 15);
}

function testConcurrentSimulation(): void {
    $list = new SafeOptimisticLockCouplingList();
    assert($list->insert(1, 'one') === true);
    assert($list->insert(3, 'three') === true);
    assert($list->insert(2, 'two') === true);
    assert($list->insert(4, 'four') === true);

    assert($list->delete(3) === true);
    assert($list->insert(2, 'two again') === false);

    $arr = $list->toArray();
    $expectedKeys = [1,2,4];
    foreach ($expectedKeys as $i => $key) {
        assert($arr[$i]['key'] === $key);
    }
}

function benchmark(): void {
    $list = new SafeOptimisticLockCouplingList();
    $start = microtime(true);
    for ($i = 0; $i < 10000; $i++) {
        $list->insert($i, $i);
    }
    $end = microtime(true);
    $duration = $end - $start;
    echo "Inserted 10000 items in {$duration} seconds\n";
}

testInsertFindDelete();
testConcurrentSimulation();
benchmark();
