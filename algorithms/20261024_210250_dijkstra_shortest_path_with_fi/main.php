<?php
declare(strict_types=1);

require_once 'types.php';
require_once 'engine.php';

// Helper function to compare distances arrays
function arraysEqual(array $a, array $b): bool
{
    ksort($a);
    ksort($b);
    return $a === $b;
}

// Test 1: Simple graph
$graph1 = new Graph();
$graph1->addEdge(0, 1, 1);
$graph1->addEdge(0, 2, 4);
$graph1->addEdge(1, 2, 2);
$graph1->addEdge(1, 3, 5);
$graph1->addEdge(2, 3, 1);
$dist1 = Dijkstra::shortestPath($graph1, 0);
$expected1 = [0 => 0, 1 => 1, 2 => 3, 3 => 4];
assert(arraysEqual($dist1, $expected1), 'Test 1 failed');

// Test 2: Single node
$graph2 = new Graph();
$dist2 = Dijkstra::shortestPath($graph2, 0);
$expected2 = [0 => 0];
assert(arraysEqual($dist2, $expected2), 'Test 2 failed');

// Test 3: Disconnected graph
$graph3 = new Graph();
$graph3->addEdge(0, 1, 2);
$dist3 = Dijkstra::shortestPath($graph3, 0);
$expected3 = [0 => 0, 1 => 2, 2 => PHP_INT_MAX];
assert(arraysEqual($dist3, $expected3), 'Test 3 failed');

// Test 4: Zero weight edges
$graph4 = new Graph();
$graph4->addEdge(0, 1, 0);
$graph4->addEdge(1, 2, 0);
$dist4 = Dijkstra::shortestPath($graph4, 0);
$expected4 = [0 => 0, 1 => 0, 2 => 0];
assert(arraysEqual($dist4, $expected4), 'Test 4 failed');

// Test 5: Multiple edges between same vertices
$graph5 = new Graph();
$graph5->addEdge(0, 1, 5);
$graph5->addEdge(0, 1, 2);
$dist5 = Dijkstra::shortestPath($graph5, 0);
$expected5 = [0 => 0, 1 => 2];
assert(arraysEqual($dist5, $expected5), 'Test 5 failed');

// Demo: print distances for a sample graph
$demoGraph = new Graph();
$demoGraph->addEdge(0, 1, 3);
$demoGraph->addEdge(0, 2, 1);
$demoGraph->addEdge(1, 2, 7);
$demoGraph->addEdge(1, 3, 5);
$demoGraph->addEdge(2, 3, 2);
$demoGraph->addEdge(3, 4, 7);
$demoGraph->addEdge(4, 5, 1);
$demoGraph->addEdge(5, 3, 2);

$demoDistances = Dijkstra::shortestPath($demoGraph, 0);
echo "Demo distances from vertex 0:\n";
foreach ($demoDistances as $vertex => $distance) {
    $distStr = ($distance === PHP_INT_MAX) ? 'INF' : (string)$distance;
    echo "  Vertex {$vertex}: {$distStr}\n";
}
?>
