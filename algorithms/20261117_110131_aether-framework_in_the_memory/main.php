<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Helper to assert exception throwing
function assertThrows(callable $fn, string $expectedMessage): void
{
    $thrown = false;
    try {
        $fn();
    } catch (MemoryException $e) {
        $thrown = true;
        assert($e->getMessage() === $expectedMessage, 'Unexpected exception message');
    }
    assert($thrown, 'Expected exception was not thrown');
}

// Unit Tests
function runTests(): void
{
    // Test 1: simple allocate, write, read, free
    $arena = new MemoryArena(64);
    $ptr = $arena->allocate(10);
    assert($ptr === 0, 'First allocation should start at 0');
    $data = "HelloWorld";
    $arena->write($ptr, $data);
    $readBack = $arena->read($ptr, 10);
    assert($readBack === $data, 'Read data must match written data');
    $arena->free($ptr);
    assert($arena->freeBytes() === 64, 'All memory should be free after free');

    // Test 2: multiple allocations and reuse freed space
    $a1 = $arena->allocate(20); // offset 0
    $a2 = $arena->allocate(15); // offset 20
    $arena->free($a1);          // free first block
    $a3 = $arena->allocate(12); // should reuse offset 0
    assert($a3 === 0, 'Allocator must reuse freed space (first-fit)');
    $arena->free($a2);
    $arena->free($a3);
    assert($arena->freeBytes() === 64, 'All memory reclaimed');

    // Test 3: over-allocation throws
    assertThrows(
        fn() => $arena->allocate(100),
        'Out of memory: cannot allocate 100 bytes'
    );

    // Test 4: double free detection
    $p = $arena->allocate(5);
    $arena->free($p);
    assertThrows(
        fn() => $arena->free($p),
        'Invalid free: no allocation at offset '.$p
    );

    // Test 5: write beyond allocated block
    $p = $arena->allocate(4);
    assertThrows(
        fn() => $arena->write($p, "ABCDE"),
        'Write error: exceeds allocated block size'
    );
    $arena->free($p);

    // Test 6: read beyond allocated block
    $p = $arena->allocate(3);
    assertThrows(
        fn() => $arena->read($p, 5),
        'Read error: exceeds allocated block size'
    );
    $arena->free($p);

    // Test 7: fragmentation handling
    $b1 = $arena->allocate(10); // 0-9
    $b2 = $arena->allocate(10); // 10-19
    $b3 = $arena->allocate(10); // 20-29
    $arena->free($b2);          // free middle
    $b4 = $arena->allocate(5);  // should fit into freed middle at offset 10
    assert($b4 === 10, 'Allocator must place block in first suitable hole');
    $arena->free($b1);
    $arena->free($b3);
    $arena->free($b4);
    assert($arena->freeBytes() === 64, 'All memory reclaimed after complex frees');
}

// Run tests
runTests();

echo "All tests passed.\n";
