<?php

declare(strict_types=1);
require_once 'core.php';

/**
 * Simple unit test helper.
 */
function assertEqual($expected, $actual, string $message = ''): void
{
    assert($expected === $actual, $message ?: "Expected {$expected}, got {$actual}");
}

/**
 * Test suite for AlgorithmLibrary.
 */
function runTests(): void
{
    $library = new AlgorithmLibrary();

    $alg1 = new Algorithm(
        'Binary Search',
        'Searches a sorted array by repeatedly dividing the search interval in half.',
        ['search', 'array', 'divide and conquer'],
        'https://github.com/TheAlgorithms/Python/blob/master/search/binary_search.py'
    );

    $alg2 = new Algorithm(
        'Merge Sort',
        'Sorts an array by dividing it into halves, sorting each half, and merging.',
        ['sort', 'array', 'divide and conquer'],
        'https://github.com/TheAlgorithms/Python/blob/master/sort/merge_sort.py'
    );

    $alg3 = new Algorithm(
        'Dijkstra',
        'Finds the shortest path between nodes in a graph.',
        ['graph', 'shortest path', 'dijkstra'],
        'https://github.com/TheAlgorithms/Python/blob/master/graph/dijkstra.py'
    );

    // Add algorithms
    $library->addAlgorithm($alg1);
    $library->addAlgorithm($alg2);
    $library->addAlgorithm($alg3);

    // Test findByName
    $found = $library->findByName('Merge Sort');
    assert($found !== null, 'Merge Sort should be found');
    assertEqual('Merge Sort', $found->getName(), 'Name mismatch');

    // Test findByTag
    $searchAlgorithms = $library->findByTag('search');
    assertEqual(1, count($searchAlgorithms), 'Search tag count');
    assertEqual('Binary Search', $searchAlgorithms[0]->getName(), 'Search algorithm name');

    $divideAlgorithms = $library->findByTag('divide and conquer');
    assertEqual(2, count($divideAlgorithms), 'Divide and conquer count');

    // Test listAll
    $all = $library->listAll();
    assertEqual(3, count($all), 'Total algorithms count');

    // Test removeAlgorithm
    $removed = $library->removeAlgorithm('Binary Search');
    assert($removed, 'Binary Search should be removed');
    $postRemove = $library->findByName('Binary Search');
    assert($postRemove === null, 'Binary Search should no longer exist');

    // Verify count after removal
    $afterRemoval = $library->listAll();
    assertEqual(2, count($afterRemoval), 'Count after removal');

    // Benchmark: add many algorithms
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        $alg = new Algorithm(
            "Alg{$i}",
            "Description {$i}",
            ['benchmark'],
            "https://example.com/alg{$i}"
        );
        $library->addAlgorithm($alg);
    }
    $duration = microtime(true) - $start;
    assert($duration < 1.0, "Benchmark addition should be fast (<1s)");

    echo "All tests passed.\n";
}

runTests();
