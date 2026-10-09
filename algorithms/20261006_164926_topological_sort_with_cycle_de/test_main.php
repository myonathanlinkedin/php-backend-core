<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

class TestMain extends TestCase
{
    public function testTopologicalSort(): void
    {
        $graph = new Graph();
        $graph->addNode(1);
        $graph->addEdge(1, 2);
        $graph->addEdge(2, 3);
        $graph->addEdge(3, 1);

        $result = $graph->topologicalSort();

        $expected = [1, 2, 3];
        $this->assertEquals($result, $expected);
    }
}
