<?php
declare(strict_types=1);

namespace AetherFlux;

require_once __DIR__ . '/engine.php';

// Enable strict assertions.
assert_options(ASSERT_ACTIVE,   1);
assert_options(ASSERT_EXCEPTION, 1);

/**
 * Utility to assert that a callable throws a MemorySafetyException with a specific message fragment.
 *
 * @param callable $fn
 * @param string   $expectedMessageFragment
 */
function assertThrows(callable $fn, string $expectedMessageFragment): void
{
    $thrown = false;
    try {
        $fn();
    } catch (MemorySafetyException $e) {
        $thrown = true;
        assert(str_contains($e->getMessage(), $expectedMessageFragment), "Exception message does not contain expected fragment.");
    }
    assert($thrown, 'Expected MemorySafetyException was not thrown.');
}

/* -------------------- Unit Tests -------------------- */

// 1. Normal allocation, write, read.
$mm = new SimpleMemoryManager();
$ptr = $mm->allocate(10);
$mm->write($ptr, 0, 42);
$mm->write($ptr, 9, 255);
assert($mm->read($ptr, 0) === 42);
assert($mm->read($ptr, 9) === 255);

// 2. Out‑of‑bounds write.
assertThrows(
    fn() => $mm->write($ptr, 10, 1),
    'out of bounds'
);

// 3. Out‑of‑bounds read.
assertThrows(
    fn() => $mm->read($ptr, -1),
    'out of bounds'
);

// 4. Double free detection.
$mm->free($ptr);
assertThrows(
    fn() => $mm->free($ptr),
    'Double free'
);

// 5. Use‑after‑free read/write.
assertThrows(
    fn() => $mm->read($ptr, 0),
    'after free'
);
assertThrows(
    fn() => $mm->write($ptr, 0, 123),
    'after free'
);

// 6. Allocation of zero bytes.
assertThrows(
    fn() => $mm->allocate(0),
    'positive'
);

// 7. Allocation exceeding heap capacity.
$big = new SimpleMemoryManager();
assertThrows(
    fn() => $big->allocate(SimpleMemoryManager::MAX_HEAP + 1),
    'Out of memory'
);

// 8. Invalid pointer usage (never allocated).
assertThrows(
    fn() => $mm->read(999999, 0),
    'invalid pointer'
);
assertThrows(
    fn() => $mm->write(999999, 0, 0),
    'invalid pointer'
);

// 9. Write with non‑byte value.
$mm2 = new SimpleMemoryManager();
$ptr2 = $mm2->allocate(5);
assertThrows(
    fn() => $mm2->write($ptr2, 0, 300),
    'byte'
);
assertThrows(
    fn() => $mm2->write($ptr2, 0, -5),
    'byte'
);

// 10. Verify internal block tracking (read‑only exposure).
$blocks = $mm2->getBlocks();
assert(count($blocks) === 1);
$block = $blocks[$ptr2];
assert($block->ptr === $ptr2);
assert($block->size === 5);
assert($block->freed === false);

// All assertions passed – the memory manager behaves as specified.
