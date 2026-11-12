<?php
declare(strict_types=1);
require_once __DIR__ . '/core.php';

/**
 * Simple test harness using PHP's built‑in assert().
 * All assertions must evaluate to true; otherwise an AssertionError is thrown.
 */
function runTests(): void
{
    // Enable strict assertions.
    assert_options(ASSERT_ACTIVE, 1);
    assert_options(ASSERT_EXCEPTION, 1);

    // Test 1: Adding nodes and retrieving them.
    $g = new Graph();
    $g->addNode('A', 'Alpha');
    $g->addNode('B'); // label defaults to id
    $nodes = $g->getNodes();
    assert(count($nodes) === 2);
    assert($nodes[0]->id === 'A' && $nodes[0]->label === 'Alpha');
    assert($nodes[1]->id === 'B' && $nodes[1]->label === 'B');

    // Test 2: Duplicate node detection.
    $duplicateCaught = false;
    try {
        $g->addNode('A');
    } catch (DuplicateNodeException) {
        $duplicateCaught = true;
    }
    assert($duplicateCaught === true);

    // Test 3: Adding a valid edge.
    $g->addEdge('A', 'B', 'A to B');
    $edges = $g->getEdges();
    assert(count($edges) === 1);
    assert($edges[0]->from === 'A' && $edges[0]->to === 'B' && $edges[0]->label === 'A to B');

    // Test 4: Edge with non‑existent source.
    $invalidSourceCaught = false;
    try {
        $g->addEdge('X', 'B');
    } catch (InvalidEdgeException) {
        $invalidSourceCaught = true;
    }
    assert($invalidSourceCaught === true);

    // Test 5: Edge with non‑existent target.
    $invalidTargetCaught = false;
    try {
        $g->addEdge('A', 'Y');
    } catch (InvalidEdgeException) {
        $invalidTargetCaught = true;
    }
    assert($invalidTargetCaught === true);

    // Test 6: HTML generation contains expected fragments.
    $html = $g->generateHtml();
    assert(str_contains($html, 'class="node" id="node-A"'));
    assert(str_contains($html, '>Alpha</div>'));
    assert(str_contains($html, 'class="node" id="node-B"'));
    assert(str_contains($html, '>B</div>'));
    assert(str_contains($html, 'class="edge"'));
    assert(str_contains($html, 'A to B'));

    // Test 7: Full document rendering includes CSS block.
    $doc = $g->renderDocument('Test Graph');
    assert(str_contains($doc, '<style>'));
    assert(str_contains($doc, '.graph {'));
    assert(str_contains($doc, '<title>Test Graph</title>'));

    // Test 8: Ensure ordering – edge appears after its source node.
    $posNodeA = strpos($html, 'id="node-A"');
    $posEdge  = strpos($html, 'class="edge"');
    assert($posNodeA !== false && $posEdge !== false && $posEdge > $posNodeA);

    // All tests passed.
    echo "All unit tests passed.\n";
}

// Execute tests when the script is run directly.
if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    runTests();
}
?>
}
