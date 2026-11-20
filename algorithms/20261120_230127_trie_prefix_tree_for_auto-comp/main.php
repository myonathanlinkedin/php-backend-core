<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

// Enable strict assertions (throws AssertionError on failure)
assert_options(ASSERT_ACTIVE,   1);
assert_options(ASSERT_EXCEPTION, 1);
assert_options(ASSERT_WARNING, 0);

/**
 * Helper to extract words from Suggestion array for easier assertions.
 *
 * @param Suggestion[] $suggestions
 *
 * @return string[]
 */
function suggestionWords(array $suggestions): array
{
    return array_map(static fn(Suggestion $s) => $s->word, $suggestions);
}

// ---------- Unit Tests ----------

// Test 1: Basic insertion and retrieval
$trie = new Trie();
$trie->insert('apple', 5);
$trie->insert('app', 3);
$trie->insert('application', 2);
$trie->insert('banana', 4);
$trie->insert('band', 1);
$trie->insert('bandana', 2);
$trie->insert('bandit', 3);

// Autocomplete for prefix 'app'
$results = $trie->autocomplete('app');
assert(suggestionWords($results) === ['apple', 'app', 'application']);
assert($results[0]->frequency === 5);
assert($results[1]->frequency === 3);
assert($results[2]->frequency === 2);

// Autocomplete for prefix 'ban' with limit 2
$results = $trie->autocomplete('ban', 2);
assert(suggestionWords($results) === ['banana', 'bandit']);
assert($results[0]->frequency === 4);
assert($results[1]->frequency === 3);

// Prefix that does not exist
$results = $trie->autocomplete('xyz');
assert($results === []);

// Empty prefix should return all words sorted globally
$results = $trie->autocomplete('', 0);
$expectedOrder = ['apple', 'banana', 'app', 'bandit', 'bandana', 'application', 'band'];
assert(suggestionWords($results) === $expectedOrder);

// Test 2: Frequency accumulation on duplicate insert
$trie->insert('app', 7); // existing word, frequency becomes 10
$results = $trie->autocomplete('app');
assert($results[0]->word === 'app');
assert($results[0]->frequency === 10);
assert($results[1]->word === 'apple');
assert($results[1]->frequency === 5);

// Test 3: Case insensitivity and trimming
$trie->insert('  APPLE  ', 1);
$results = $trie->autocomplete('ApP');
assert($results[0]->word === 'app'); // now highest frequency (10)
assert($results[1]->word === 'apple'); // frequency 6 after extra insert
assert($results[1]->frequency === 6);

// Test 4: Zero or negative frequency should be ignored
$trie->insert('newword', 0);
$trie->insert('another', -3);
$results = $trie->autocomplete('new');
assert($results === []);

// Test 5: Limit edge cases
$trie->insert('cat', 2);
$trie->insert('cater', 1);
$trie->insert('cattle', 3);
$results = $trie->autocomplete('cat', 1);
assert(count($results) === 1);
assert($results[0]->word === 'cattle'); // highest frequency under 'cat'

// ---------- Demo ----------
echo "All unit tests passed.\n";
echo "Demo autocomplete for prefix 'ba':\n";
$demo = $trie->autocomplete('ba');
foreach ($demo as $s) {
    echo $s->word . ' (freq=' . $s->frequency . ")\n";
}
?>
