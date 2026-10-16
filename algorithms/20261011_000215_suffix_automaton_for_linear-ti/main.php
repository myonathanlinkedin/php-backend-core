<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Enable strict assertions
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);
assert_options(ASSERT_WARNING, 1);

/* ---------- Unit Tests ---------- */

function test_basic(): void
{
    $sam = new SuffixAutomaton('ababa');

    // Substring existence
    assert($sam->contains('a') === true);
    assert($sam->contains('b') === true);
    assert($sam->contains('ab') === true);
    assert($sam->contains('ba') === true);
    assert($sam->contains('aba') === true);
    assert($sam->contains('bab') === true);
    assert($sam->contains('ababa') === true);
    assert($sam->contains('') === true);          // empty string is always a substring
    assert($sam->contains('c') === false);
    assert($sam->contains('aa') === false);
    assert($sam->contains('abb') === false);

    // Occurrence counts
    assert($sam->countOccurrences('a') === 3);
    assert($sam->countOccurrences('b') === 2);
    assert($sam->countOccurrences('ab') === 2);
    assert($sam->countOccurrences('ba') === 2);
    assert($sam->countOccurrences('aba') === 2);
    assert($sam->countOccurrences('bab') === 1);
    assert($sam->countOccurrences('ababa') === 1);
    assert($sam->countOccurrences('c') === 0);
    assert($sam->countOccurrences('aa') === 0);
}

function test_edge_cases(): void
{
    // Empty source string
    $samEmpty = new SuffixAutomaton('');
    assert($samEmpty->contains('') === true);
    assert($samEmpty->contains('a') === false);
    assert($samEmpty->countOccurrences('') === 0);
    assert($samEmpty->countOccurrences('a') === 0);

    // Single character source
    $samOne = new SuffixAutomaton('x');
    assert($samOne->contains('x') === true);
    assert($samOne->contains('') === true);
    assert($samOne->contains('xx') === false);
    assert($samOne->countOccurrences('x') === 1);
    assert($samOne->countOccurrences('') === 0);
    assert($samOne->countOccurrences('xx') === 0);

    // Repeated character source
    $samRep = new SuffixAutomaton('aaaa');
    assert($samRep->contains('a') === true);
    assert($samRep->contains('aa') === true);
    assert($samRep->contains('aaa') === true);
    assert($samRep->contains('aaaa') === true);
    assert($samRep->contains('aaaaa') === false);
    assert($samRep->countOccurrences('a') === 4);
    assert($samRep->countOccurrences('aa') === 3);
    assert($samRep->countOccurrences('aaa') === 2);
    assert($samRep->countOccurrences('aaaa') === 1);
}

function test_random_strings(): void
{
    $source = 'abcababc';
    $sam = new SuffixAutomaton($source);
    $substrings = [
        'a' => 3, 'b' => 3, 'c' => 2,
        'ab' => 3, 'bc' => 2, 'ca' => 1,
        'abc' => 2, 'cab' => 1, 'bca' => 1,
        'abcab' => 1, 'bcaba' => 0,
    ];
    foreach ($substrings as $sub => $cnt) {
        assert($sam->contains($sub) === ($cnt > 0));
        assert($sam->countOccurrences($sub) === $cnt);
    }
}

// Run tests
test_basic();
test_edge_cases();
test_random_strings();

echo "All tests passed.\n";
?>
