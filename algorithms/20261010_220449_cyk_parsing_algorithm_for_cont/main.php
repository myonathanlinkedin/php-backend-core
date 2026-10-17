<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

/**
 * Helper to create a Production with a variable number of RHS symbols.
 *
 * @param string $left
 * @param string ...$right
 * @return Production
 */
function prod(string $left, string ...$right): Production
{
    return new Production($left, $right);
}

/* ----------------------------------------------------------------------
 * Test 1 – Grammar for aⁿbⁿ (n ≥ 1)
 *
 * CNF version:
 *   S → A B
 *   A → T_a A | T_a
 *   B → B T_b | T_b
 *   T_a → a
 *   T_b → b
 * ---------------------------------------------------------------------- */
$grammar1 = new Grammar(
    'S',
    [
        prod('S', 'A', 'B'),
        prod('A', 'T_a', 'A'),
        prod('A', 'T_a'),
        prod('B', 'B', 'T_b'),
        prod('B', 'T_b'),
        prod('T_a', 'a'),
        prod('T_b', 'b'),
    ]
);
$parser1 = new CYKParser($grammar1);

// Positive cases
assert($parser1->parse('ab') === true, 'ab should be accepted');
assert($parser1->parse('aabb') === true, 'aabb should be accepted');
assert($parser1->parse('aaabbb') === true, 'aaabbb should be accepted');

// Negative cases
assert($parser1->parse('aab') === false, 'aab should be rejected');
assert($parser1->parse('abb') === false, 'abb should be rejected');
assert($parser1->parse('') === false, 'empty string should be rejected (no ε‑production)');

/* ----------------------------------------------------------------------
 * Test 2 – Grammar that generates the empty string only (S → ε)
 * ---------------------------------------------------------------------- */
$grammar2 = new Grammar(
    'S',
    [
        prod('S') // ε‑production: RHS is empty
    ]
);
$parser2 = new CYKParser($grammar2);
assert($parser2->parse('') === true, 'empty string should be accepted by ε‑grammar');
assert($parser2->parse('a') === false, 'non‑empty string should be rejected by ε‑grammar');

/* ----------------------------------------------------------------------
 * Test 3 – Simple arithmetic expressions:  (a|b)*
 * Grammar in CNF:
 *   S → S S | a | b
 * ---------------------------------------------------------------------- */
$grammar3 = new Grammar(
    'S',
    [
        prod('S', 'S', 'S'), // S → S S
        prod('S', 'a'),      // S → a
        prod('S', 'b'),      // S → b
    ]
);
$parser3 = new CYKParser($grammar3);
assert($parser3->parse('') === false, 'empty string not in (a|b)* without ε');
assert($parser3->parse('a') === true, 'single a accepted');
assert($parser3->parse('b') === true, 'single b accepted');
assert($parser3->parse('ab') === true, 'ab accepted');
assert($parser3->parse('baab') === true, 'baab accepted');
assert($parser3->parse('abc') === false, 'c is not a terminal in the grammar');

/* ----------------------------------------------------------------------
 * Test 4 – Ambiguous grammar where multiple non‑terminals derive the same RHS
 *
 * Grammar:
 *   S → A B | C D
 *   A → a
 *   B → b
 *   C → a
 *   D → b
 * ---------------------------------------------------------------------- */
$grammar4 = new Grammar(
    'S',
    [
        prod('S', 'A', 'B'),
        prod('S', 'C', 'D'),
        prod('A', 'a'),
        prod('B', 'b'),
        prod('C', 'a'),
        prod('D', 'b'),
    ]
);
$parser4 = new CYKParser($grammar4);
assert($parser4->parse('ab') === true, 'ab should be accepted despite ambiguity');
assert($parser4->parse('aa') === false, 'aa should be rejected');

/* ----------------------------------------------------------------------
 * All assertions passed – demo complete.
 * ---------------------------------------------------------------------- */
echo "All CYK parser tests passed.\n";
