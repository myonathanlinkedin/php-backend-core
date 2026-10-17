<?php

declare(strict_types=1);
require_once 'core.php';

function testAllocationAndFree(): void
{
    $allocator = new BuddyAllocator(1024, 16);
    $a = $allocator->allocate(100);
    assert($a !== null);
    $b = $allocator->allocate(200);
    assert($b !== null);
    $c = $allocator->allocate(50);
    assert($c !== null);
    $allocator->free($b);
    $d = $allocator->allocate(180);
    assert($d !== null);
    $allocator->free($a);
    $allocator->free($c);
    $allocator->free($d);
    // After freeing all, entire memory should be one block
    $e = $allocator->allocate(1024);
    assert($e === 0);
    $allocator->free($e);
}

function testMergeBuddy(): void
{
    $allocator = new BuddyAllocator(256, 16);
    $a = $allocator->allocate(64);
    $b = $allocator->allocate(64);
    assert($a !== null && $b !== null);
    $allocator->free($a);
    $allocator->free($b);
    // After freeing both, should merge into 128 block
    $c = $allocator->allocate(128);
    assert($c !== null);
    $allocator->free($c);
}

function testInsufficientMemory(): void
{
    $allocator = new BuddyAllocator(128, 16);
    $blocks = [];
    for ($i = 0; $i < 8; $i++) {
        $blocks[] = $allocator->allocate(16);
        assert($blocks[$i] !== null);
    }
    // Next allocation should fail
    $extra = $allocator->allocate(16);
    assert($extra === null);
    foreach ($blocks as $offset) {
        $allocator->free($offset);
    }
}

function testZeroSizeAllocation(): void
{
    $allocator = new BuddyAllocator(512, 16);
    $zero = $allocator->allocate(0);
    assert($zero === null);
}

function testInvalidFree(): void
{
    $allocator = new BuddyAllocator(512, 16);
    $a = $allocator->allocate(32);
    assert($a !== null);
    try {
        $allocator->free($a + 1);
        assert(false); // Should not reach here
    } catch (InvalidArgumentException $e) {
        assert(true);
    }
    $allocator->free($a);
}

function runAllTests(): void
{
    testAllocationAndFree();
    testMergeBuddy();
    testInsufficientMemory();
    testZeroSizeAllocation();
    testInvalidFree();
    echo "All tests passed.\n";
}

runAllTests();
?>
