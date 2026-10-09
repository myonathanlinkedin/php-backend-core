<?php

require_once __DIR__ . '/types.php';
require_once __DIR__ . '/engine.php';

use BuddyAllocator\BuddyAllocator;

// Create allocator with 1024 bytes pool
$allocator = new BuddyAllocator(1024);

// Test allocation of 100 bytes
$ptr1 = $allocator->allocate(100);
assert($ptr1 !== null, 'Allocation of 100 bytes should succeed');
assert($ptr1 === 0, 'First allocation should start at offset 0');

// Allocate 200 bytes
$ptr2 = $allocator->allocate(200);
assert($ptr2 !== null, 'Allocation of 200 bytes should succeed');
assert($ptr2 === 256, 'Second allocation should start at offset 256');

// Allocate 300 bytes
$ptr3 = $allocator->allocate(300);
assert($ptr3 !== null, 'Allocation of 300 bytes should succeed');
assert($ptr3 === 512, 'Third allocation should start at offset 512');

// Allocate 400 bytes should fail (only 256 bytes left)
$ptr4 = $allocator->allocate(400);
assert($ptr4 === null, 'Allocation of 400 bytes should fail due to insufficient space');

// Free second allocation
$allocator->free($ptr2);

// Allocate 400 bytes again, should succeed now (merged block)
$ptr5 = $allocator->allocate(400);
assert($ptr5 !== null, 'Allocation of 400 bytes after freeing should succeed');
assert($ptr5 === 256, 'Allocated block should reuse freed space at offset 256');

// Edge case: free invalid pointer
$allocator->free(9999); // should not crash

// Edge case: allocate zero bytes
$ptrZero = $allocator->allocate(0);
assert($ptrZero === null, 'Allocation of zero bytes should return null');

// Edge case: allocate more than pool
$ptrLarge = $allocator->allocate(2000);
assert($ptrLarge === null, 'Allocation larger than pool should return null');

// Test merging after multiple frees
$allocator->free($ptr1);
$allocator->free($ptr3);
$allocator->free($ptr5);

// After freeing all, entire pool should be one free block
$ptrAll = $allocator->allocate(1024);
assert($ptrAll !== null, 'Allocation of entire pool after freeing all should succeed');
assert($ptrAll === 0, 'Allocated block should start at offset 0');

// Demo: allocate 512 bytes
$ptrDemo = $allocator->allocate(512);
assert($ptrDemo !== null, 'Demo allocation of 512 bytes should succeed');
echo "Demo allocation at offset {$ptrDemo}\n";
