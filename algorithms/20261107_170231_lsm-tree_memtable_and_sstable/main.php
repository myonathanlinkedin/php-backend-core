<?php
declare(strict_types=1);
require_once 'types.php';
require_once 'engine.php';

function assertTrue(bool $cond, string $msg = ''): void {
    assert($cond, $msg);
}

// Test 1: basic put/get and automatic flush
$engine = new LSMEngine(memTableMaxEntries: 3, levelMaxTables: 2);
$engine->put('a', '1');
assertTrue($engine->get('a') === '1', 'Get after put a');
$engine->put('b', '2');
$engine->put('c', '3'); // triggers flush (memsize reaches 3)
assertTrue($engine->getMemTableSize() === 0, 'MemTable cleared after flush');
assertTrue($engine->get('b') === '2', 'Get after flush b');

// Test 2: delete handling and compaction trigger
$engine->delete('b');
assertTrue($engine->get('b') === null, 'Deleted key returns null');
$engine->put('d', '4');
$engine->put('e', '5');
$engine->put('f', '6'); // second flush, now level 0 has 2 tables -> compaction
$counts = $engine->getLevelTableCounts();
assertTrue(($counts[0] ?? 0) === 0, 'Level 0 cleared after compaction');
assertTrue(($counts[1] ?? 0) === 1, 'One table at level 1 after compaction');

// Test 3: overlapping keys across levels, ensuring newest value wins
$engine->put('a', '10'); // new memtable entry
$engine->put('g', '7');
$engine->put('h', '8'); // flush creates level0 table
$engine->put('a', '11'); // newer value for a in memtable
$engine->put('i', '9');
$engine->put('j', '10'); // flush triggers compaction merging level0 tables
assertTrue($engine->get('a') === '11', 'Latest value of a after compaction');
assertTrue($engine->get('g') === '7', 'Value g persists');
assertTrue($engine->get('b') === null, 'Deleted b remains null');

// Test 4: additional keys to verify ordering does not affect retrieval
$engine->put('z', '100');
$engine->put('y', '99');
$engine->put('x', '98'); // flush
assertTrue($engine->get('z') === '100', 'Get z');
assertTrue($engine->get('x') === '98', 'Get x');

echo "All LSM Engine tests passed.\n";
