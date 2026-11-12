<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

// Configure assertions to throw exceptions on failure.
assert_options(ASSERT_EXCEPTION, 1);

/**
 * Helper to run a test case and report success.
 *
 * @param string $name
 * @param callable $fn
 */
function runTest(string $name, callable $fn): void
{
    try {
        $fn();
        echo "[PASS] {$name}\n";
    } catch (Throwable $e) {
        echo "[FAIL] {$name}: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/* ---------- Test Suite ---------- */

runTest('Basic put/get without flush', function () {
    $engine = new LSMEngine(memTableThreshold: 10); // high threshold to avoid auto flush
    $engine->put('a', '1');
    $engine->put('b', '2');
    assert($engine->get('a') === '1');
    assert($engine->get('b') === '2');
    assert($engine->get('c') === null);
});

runTest('Automatic flush on threshold', function () {
    $engine = new LSMEngine(memTableThreshold: 3);
    $engine->put('k1', 'v1');
    $engine->put('k2', 'v2');
    // Not flushed yet.
    assert(count($engine->getSSTables()) === 0);
    $engine->put('k3', 'v3'); // triggers flush
    assert(count($engine->getSSTables()) === 1);
    // MemTable should be empty now.
    assert($engine->getMemTable()->size() === 0);
    // Reads should still work.
    assert($engine->get('k2') === 'v2');
});

runTest('Overwrite key across MemTable and SSTable', function () {
    $engine = new LSMEngine(memTableThreshold: 2);
    $engine->put('x', 'old');
    $engine->put('y', 'val');
    // Flush occurs here.
    $engine->put('x', 'new'); // new value in MemTable
    assert($engine->get('x') === 'new');
    // Force flush to move 'new' into SSTable.
    $engine->flushMemTable();
    assert($engine->get('x') === 'new');
});

runTest('Compaction merges two oldest SSTables', function () {
    $engine = new LSMEngine(memTableThreshold: 2, sstableCompactionThreshold: 3);
    // Create three SSTables via forced flushes.
    $engine->put('a', '1'); // 1
    $engine->put('b', '2'); // flush -> SSTable #1
    $engine->flushMemTable();

    $engine->put('a', '10'); // 2
    $engine->put('c', '3');  // flush -> SSTable #2
    $engine->flushMemTable();

    $engine->put('d', '4');  // 3
    $engine->put('e', '5');  // flush -> SSTable #3 triggers compaction
    $engine->flushMemTable();

    // After compaction we should have exactly 2 SSTables (merged + newest)
    $sstables = $engine->getSSTables();
    assert(count($sstables) === 2);

    // Verify merged data: key 'a' should have value from newer of the two merged tables (generation higher)
    // The two oldest tables had 'a' => '1' (gen1) and 'a' => '10' (gen2). After merging, 'a' => '10'.
    $mergedData = $sstables[0]->getData(); // oldest after compaction
    assert($mergedData['a'] === '10');
    assert($mergedData['b'] === '2');
    assert($mergedData['c'] === '3');

    // Newest SSTable contains 'd' and 'e'.
    $newest = $sstables[1];
    assert($newest->get('d') === '4');
    assert($newest->get('e') === '5');

    // Global reads must see the latest values.
    assert($engine->get('a') === '10');
    assert($engine->get('d') === '4');
});

runTest('Read fallback through multiple SSTables', function () {
    $engine = new LSMEngine(memTableThreshold: 2, sstableCompactionThreshold: 5);
    $engine->put('k', 'v1');
    $engine->put('x', 'x1');
    $engine->flushMemTable(); // SSTable #1

    $engine->put('k', 'v2');
    $engine->put('y', 'y1');
    $engine->flushMemTable(); // SSTable #2

    // No compaction yet.
    assert($engine->get('k') === 'v2'); // newest value
    assert($engine->get('x') === 'x1');
    assert($engine->get('y') === 'y1');
});

/* ---------- Demo ---------- */
echo "\n--- Demo Run ---\n";
$engine = new LSMEngine(memTableThreshold: 3);
$engine->put('apple', 'red');
$engine->put('banana', 'yellow');
$engine->put('cherry', 'red'); // triggers flush
$engine->put('date', 'brown');
$engine->put('elderberry', 'black'); // triggers second flush

foreach (['apple', 'banana', 'cherry', 'date', 'elderberry'] as $fruit) {
    $color = $engine->get($fruit);
    echo "{$fruit}: {$color}\n";
}
?>
