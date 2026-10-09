<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

use Core\Deck;
use Core\CombinationGenerator;

// Configure strict assertions.
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_EXCEPTION, 1);

// ---------- Unit Tests ----------
$deck = new Deck();
$cards = $deck->getCards();
$k = 5;
$gen = new CombinationGenerator($cards, $k);

// Test total number of 5‑card hands.
$expectedTotal = 2598960; // 52 choose 5
assert($gen->getTotal() === $expectedTotal, 'Total combinations mismatch.');

// Test first combination (indices 0‑4).
$first = $gen->getCombination(0);
assert(count($first) === $k, 'First combination size mismatch.');
assert((string)$first[0] === 'A♠', 'First card incorrect.');
assert((string)$first[1] === '2♠', 'Second card incorrect.');
assert((string)$first[2] === '3♠', 'Third card incorrect.');
assert((string)$first[3] === '4♠', 'Fourth card incorrect.');
assert((string)$first[4] === '5♠', 'Fifth card incorrect.');

// Test last combination (indices 47‑51).
$last = $gen->getCombination($expectedTotal - 1);
assert((string)$last[0] === '10♣', 'Last combination first card incorrect.');
assert((string)$last[1] === 'J♣', 'Last combination second card incorrect.');
assert((string)$last[2] === 'Q♣', 'Last combination third card incorrect.');
assert((string)$last[3] === 'K♣', 'Last combination fourth card incorrect.');
assert((string)$last[4] === 'A♣', 'Last combination fifth card incorrect.');

// Random spot check: index 12345.
$sample = $gen->getCombination(12345);
assert(count($sample) === $k, 'Sample combination size mismatch.');
// Ensure all cards are distinct.
$unique = array_unique(array_map('strval', $sample));
assert(count($unique) === $k, 'Sample combination contains duplicates.');

// Verify that two different indices produce different hands.
$handA = $gen->getCombination(1000);
$handB = $gen->getCombination(1001);
assert((string)implode(',', $handA) !== (string)implode(',', $handB), 'Adjacent combinations should differ.');

// Exception handling tests.
$exceptionCaught = false;
try {
    $gen->getCombination(-1);
} catch (\OutOfRangeException $e) {
    $exceptionCaught = true;
}
assert($exceptionCaught, 'Negative index should throw OutOfRangeException.');

$exceptionCaught = false;
try {
    $gen->getCombination($expectedTotal);
} catch (\OutOfRangeException $e) {
    $exceptionCaught = true;
}
assert($exceptionCaught, 'Index equal to total should throw OutOfRangeException.');

// ---------- Simple Benchmark ----------
$start = microtime(true);
$counter = 0;
foreach ($gen->getIterator() as $idx => $hand) {
    // No operation; just iterate.
    $counter++;
    // Break early to keep runtime reasonable in CI.
    if ($counter >= 10000) {
        break;
    }
}
$elapsed = microtime(true) - $start;
printf("Iterated %d combinations in %.4f seconds (%.2f combos/sec)\n",
    $counter, $elapsed, $counter / $elapsed);

// ---------- Entry Point ----------
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "All tests passed.\n";
}
?>
