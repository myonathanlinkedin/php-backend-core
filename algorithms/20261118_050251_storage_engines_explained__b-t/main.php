<?php

require_once 'core.php';

function testBTree(): void {
    $bt = new BTree(2);
    $keys = ['a','b','c','d','e','f','g','h','i','j'];
    foreach ($keys as $k) {
        $bt->insert($k);
    }
    foreach ($keys as $k) {
        assert($bt->search($k) !== null, "BTree should find key $k");
    }
    assert($bt->search('z') === null, "BTree should not find missing key");
}

function testLSM(): void {
    $lsm = new LSMTree(3);
    $lsm->insert('a','1');
    $lsm->insert('b','2');
    $lsm->insert('c','3');
    // flush threshold reached
    $lsm->insert('d','4');
    assert($lsm->search('a') === '1', "LSM should find key a");
    assert($lsm->search('d') === '4', "LSM should find key d");
    assert($lsm->search('z') === null, "LSM should not find missing key");
    // merge tables
    $lsm->merge();
    assert($lsm->search('b') === '2', "LSM after merge should find key b");
}

testBTree();
testLSM();

echo "All tests passed.\n";
