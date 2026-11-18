<?php
declare(strict_types=1);
require_once 'core.php';

// Simple deterministic unit tests using assert()
function runTests(): void
{
    // Test 1: Basic put/get
    $lsm = new LSMTree(2); // tiny memtable to force early flushes
    $lsm->put('a', '1');
    assert($lsm->get('a') === '1', 'Put/Get a');

    // Test 2: Flush to SSTable
    $lsm->put('b', '2'); // mem full, triggers flush
    assert($lsm->get('a') === '1', 'After flush a');
    assert($lsm->get('b') === '2', 'After flush b');

    // Test 3: Delete handling
    $lsm->delete('a');
    assert($lsm->get('a') === null, 'Deleted a');
    assert($lsm->get('b') === '2', 'b still present');

    // Test 4: Multiple flushes and compaction trigger
    $lsm->put('c', '3'); // mem: c
    $lsm->put('d', '4'); // mem full -> flush (c,d)
    $lsm->put('e', '5'); // mem: e
    $lsm->put('f', '6'); // mem full -> flush (e,f)
    $lsm->put('g', '7'); // mem: g
    $lsm->put('h', '8'); // mem full -> flush (g,h)
    $lsm->put('i', '9'); // mem: i
    $lsm->put('j', '10'); // mem full -> flush (i,j) => now 5 SSTables at level 0, triggers compaction

    // After compaction, level 0 should be empty, level 1 should have 1 SSTable
    $levels = $lsm->getLevels();
    assert(count($levels[0] ?? []) === 0, 'Level 0 empty after compaction');
    assert(count($levels[1] ?? []) === 1, 'Level 1 has one SSTable after compaction');

    // Verify data integrity post‑compaction
    assert($lsm->get('b') === '2', 'b after compaction');
    assert($lsm->get('c') === '3', 'c after compaction');
    assert($lsm->get('d') === '4', 'd after compaction');
    assert($lsm->get('e') === '5', 'e after compaction');
    assert($lsm->get('f') === '6', 'f after compaction');
    assert($lsm->get('g') === '7', 'g after compaction');
    assert($lsm->get('h') === '8', 'h after compaction');
    assert($lsm->get('i') === '9', 'i after compaction');
    assert($lsm->get('j') === '10', 'j after compaction');

    // Test 5: Deletion persists through compaction
    $lsm->delete('e');
    $lsm->delete('f');
    // Force flush and compaction
    $lsm->put('k', '11');
    $lsm->put('l', '12'); // triggers flush
    $lsm->put('m', '13');
    $lsm->put('n', '14'); // triggers flush, may cause another compaction

    assert($lsm->get('e') === null, 'e deleted persists');
    assert($lsm->get('f') === null, 'f deleted persists');
    assert($lsm->get('k') === '11', 'k present');
    assert($lsm->get('l') === '12', 'l present');
    assert($lsm->get('m') === '13', 'm present');
    assert($lsm->get('n') === '14', 'n present');

    // Test 6: Overwrite semantics (newer seq wins)
    $lsm->put('b', '20');
    assert($lsm->get('b') === '20', 'b overwritten with newer value');

    echo "All tests passed.\n";
}

runTests();
