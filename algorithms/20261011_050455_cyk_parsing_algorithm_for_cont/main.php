<?php

require_once 'types.php';
require_once 'engine.php';

// Helper function to assert boolean result
function assertParse(string $input, Grammar $grammar, bool $expected): void
{
    $result = cykParse($input, $grammar);
    assert($result === $expected, "Parsing '{$input}' expected " . ($expected ? 'true' : 'false') . " but got " . ($result ? 'true' : 'false'));
}

// Test 1: Grammar for even number of 'a's
$gEven = new Grammar('S', ['S', 'A'], ['a']);
$gEven->addProduction('S', ['A', 'A']);
$gEven->addProduction('A', ['a']);

assertParse('aa', $gEven, true);
assertParse('aaaa', $gEven, true);
assertParse('a', $gEven, false);
assertParse('', $gEven, false);

// Test 2: Grammar that accepts only epsilon
$gEpsilon = new Grammar('S', ['S'], []);
$gEpsilon->addProduction('S', []); // epsilon production

assertParse('', $gEpsilon, true);
assertParse('a', $gEpsilon, false);

// Test 3: Grammar for single string "ab"
$gAB = new Grammar('S', ['S', 'A', 'B'], ['a', 'b']);
$gAB->addProduction('S', ['A', 'B']);
$gAB->addProduction('A', ['a']);
$gAB->addProduction('B', ['b']);

assertParse('ab', $gAB, true);
assertParse('a', $gAB, false);
assertParse('b', $gAB, false);
assertParse('aba', $gAB, false);

// Test 4: Grammar with multiple productions for same RHS
$gMulti = new Grammar('S', ['S', 'A', 'B'], ['a', 'b']);
$gMulti->addProduction('S', ['A', 'B']);
$gMulti->addProduction('S', ['B', 'A']);
$gMulti->addProduction('A', ['a']);
$gMulti->addProduction('B', ['b']);

assertParse('ab', $gMulti, true);
assertParse('ba', $gMulti, true);
assertParse('aa', $gMulti, false);
assertParse('bb', $gMulti, false);

// Test 5: Input with invalid terminal
$gInvalid = new Grammar('S', ['S', 'A'], ['a']);
$gInvalid->addProduction('S', ['A', 'A']);
$gInvalid->addProduction('A', ['a']);

assertParse('c', $gInvalid, false);
assertParse('ac', $gInvalid, false);

// All tests passed
echo "All tests passed.\n";
?>
