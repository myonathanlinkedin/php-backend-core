<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';
require_once __DIR__ . '/engine.php';

// Enable strict assertion handling
ini_set('assert.exception', '1');
ini_set('assert.active', '1');

// ---------------------------------------------------------------------
// Unit‑test suite for the routing engine
// ---------------------------------------------------------------------

$graph = new Graph();

// ----- Node creation -----
$graph->addNode(new Node('A'));
$graph->addNode(new Node('B'));
$graph->addNode(new Node('C'));
$graph->addNode(new Node('D'));

// ----- Edge creation (directed) -----
$graph->addEdge(new Edge('A', 'B', 1));
$graph->addEdge(new Edge('B', 'D', 4));
$graph->addEdge(new Edge('A', 'C', 2));
$graph->addEdge(new Edge('C', 'D', 1));
$graph->addEdge(new Edge('B', 'C', 2)); // alternative route

// ----- Basic shortest‑path test -----
$result = $graph->getShortestPath('A', 'D');
assert($result !== null);
assert($result->path === ['A', 'C', 'D']);
assert($result->totalCost === 3);

// ----- Failover: bring node B down -----
$graph->setNodeStatus('B', NodeStatus::DOWN);
$resultBDown = $graph->getShortestPath('A', 'D');
assert($resultBDown !== null);
assert($resultBDown->path === ['A', 'C', 'D']);
assert($resultBDown->totalCost === 3);

// ----- Failover: bring node C down (both B and C down) -----
$graph->setNodeStatus('C', NodeStatus::DOWN);
$resultAllDown = $graph->getShortestPath('A', 'D');
assert($resultAllDown === null); // No viable route

// ----- Cost control: add high‑cost alternative and verify it is ignored -----
$graph->addNode(new Node('E'));
$graph->addEdge(new Edge('A', 'E', 10));
$graph->addEdge(new Edge('E', 'D', 10));

// Reactivate C, keep B down
$graph->setNodeStatus('C', NodeStatus::UP);
$graph->setNodeStatus('B', NodeStatus::DOWN);
$resultCostControl = $graph->getShortestPath('A', 'D');
assert($resultCostControl !== null);
assert($resultCostControl->path === ['A', 'C', 'D']);
assert($resultCostControl->totalCost === 3);

// ----- Edge case: zero‑cost direct edge overrides all others -----
$graph->addEdge(new Edge('A', 'D', 0));
$resultZeroCost = $graph->getShortestPath('A', 'D');
assert($resultZeroCost !== null);
assert($resultZeroCost->path === ['A', 'D']);
assert($resultZeroCost->totalCost === 0);

// ----- Edge case: self‑loop (should not affect shortest path) -----
$graph->addEdge(new Edge('D', 'D', 0));
$resultSelfLoop = $graph->getShortestPath('A', 'D');
assert($resultSelfLoop !== null);
assert($resultSelfLoop->path === ['A', 'D']);
assert($resultSelfLoop->totalCost === 0);

// ----- Edge case: unreachable target (node not present) -----
$graph->addNode(new Node('X', NodeStatus::UP));
$resultMissing = $graph->getShortestPath('A', 'X');
assert($resultMissing === null);

// All assertions passed if execution reaches this point.
echo "All routing engine tests passed.\n";
