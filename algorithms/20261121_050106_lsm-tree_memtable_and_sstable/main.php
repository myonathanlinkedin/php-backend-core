<?php
declare(strict_types=1);
require_once __DIR__ . '/core.php';

// Enable strict assertions
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

// Helper to compare expected vs actual
function expectEqual($actual, $expected, string $msg = ''): void
{
    assert($actual === $expected, $msg ?: "Expected " . var_export($expected, true) . " got " . var_export($actual, true));
}

// 1. Basic put/get within memtable capacity
$engine = new LSMTreeEngine(memCapacity: 3);
$engine->put('a', '1');
$engine->put('b', '2');
expectEqual($engine->get('a'), '1', 'Basic get a');
expectEqual($engine->get('b'), '2', 'Basic get b');
assert($engine->getMemTableSize() === 2, 'MemTable size after two puts');

// 2. Flush on capacity overflow
$engine->put('c', '3'); // fills memtable (capacity 3)
$engine->put('d', '4'); // triggers flush of previous 3 entries
expectEqual($engine->get('a'), '1', 'Post-flush get a');
expectEqual($engine->get('d'), '4', 'Post-flush get d');
assert($engine->getMemTableSize() === 1, 'MemTable size after flush');
assert($engine->getLevelCount() === 1, 'One level after first flush');
assert($engine->getSSTableCountAtLevel(0) === 1, 'One SSTable at level 0');

// 3. Delete semantics
$engine->delete('b');
expectEqual($engine->get('b'), null, 'Deleted key b returns null');
$engine->put('e', '5'); // fill memtable to trigger another flush
$engine->put('f', '6'); // triggers flush
assert($engine->getLevelCount() === 2, 'Two levels after second flush');
assert($engine->getSSTableCountAtLevel(0) === 1, 'Level 0 has one SSTable after compaction');
assert($engine->getSSTableCountAtLevel(1) === 1, 'Level 1 has one SSTable after compaction');

// 4. Overwrite after delete
$engine->put('b', '22');
expectEqual($engine->get('b'), '22', 'Overwrite after delete');

// 5. Compaction correctness
// Insert many keys to force multiple SSTables at level 0
for ($i = 0; $i < 12; $i++) {
    $engine->put('k' . $i, (string)($i * 10));
}
assert($engine->getLevelCount() >= 2, 'Compaction created higher levels');
for ($i = 0; $i < 12; $i++) {
    expectEqual($engine->get('k' . $i), (string)($i * 10), "Value for k$i after compaction");
}

// 6. Latest value wins across levels
$engine->put('a', '100'); // newer value for existing key
expectEqual($engine->get('a'), '100', 'Latest value for a after multiple levels');

// 7. Tombstone removal after compaction
$engine->delete('c'); // tombstone for key c
$engine->put('c', '300'); // resurrect with new value
expectEqual($engine->get('c'), '300', 'Resurrected key c after tombstone');

// All tests passed if execution reaches this point
echo "All LSM-Tree tests passed.\n";
