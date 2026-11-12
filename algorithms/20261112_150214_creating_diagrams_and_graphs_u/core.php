<?php
declare(strict_types=1);

/**
 * Exception thrown when attempting to add a node that already exists.
 */
class DuplicateNodeException extends \Exception {}

/**
 * Exception thrown when an edge references a non‑existent node.
 */
class InvalidEdgeException extends \Exception {}

/**
 * Represents a node in a directed graph.
 */
final class Node
{
    public string $id;
    public string $label;

    public function __construct(string $id, string $label = '')
    {
        $this->id = $id;
        $this->label = $label !== '' ? $label : $id;
    }
}

/**
 * Represents a directed edge between two nodes.
 */
final class Edge
{
    public string $from;
    public string $to;
    public string $label;

    public function __construct(string $from, string $to, string $label = '')
    {
        $this->from = $from;
        $this->to = $to;
        $this->label = $label !== '' ? $label : $from . '→' . $to;
    }
}

/**
 * Core graph data structure with HTML/CSS rendering capabilities.
 */
final class Graph
{
    /** @var array<string, Node> */
    private array $nodes = [];

    /** @var Edge[] */
    private array $edges = [];

    /**
     * Adds a node to the graph.
     *
     * @throws DuplicateNodeException if $id already exists.
     */
    public function addNode(string $id, string $label = ''): void
    {
        if (isset($this->nodes[$id])) {
            throw new DuplicateNodeException("Node '{$id}' already exists.");
        }
        $this->nodes[$id] = new Node($id, $label);
    }

    /**
     * Adds a directed edge to the graph.
     *
     * @throws InvalidEdgeException if either endpoint does not exist.
     */
    public function addEdge(string $from, string $to, string $label = ''): void
    {
        if (!isset($this->nodes[$from])) {
            throw new InvalidEdgeException("Source node '{$from}' does not exist.");
        }
        if (!isset($this->nodes[$to])) {
            throw new InvalidEdgeException("Target node '{$to}' does not exist.");
        }
        $this->edges[] = new Edge($from, $to, $label);
    }

    /**
     * @return Node[]
     */
    public function getNodes(): array
    {
        return array_values($this->nodes);
    }

    /**
     * @return Edge[]
     */
    public function getEdges(): array
    {
        return $this->edges;
    }

    /**
     * Generates a minimal CSS block that styles the graph.
     */
    public function generateCss(): string
    {
        return <<<CSS
.graph {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    font-family: Arial, sans-serif;
}
.graph .node {
    padding: 0.5rem 1rem;
    margin: 0.25rem 0;
    border: 2px solid #4a90e2;
    border-radius: 4px;
    background-color: #e6f2ff;
    position: relative;
}
.graph .edge {
    margin: 0.1rem 0 0.1rem 1.5rem;
    font-size: 0.9rem;
    color: #555;
}
CSS;
    }

    /**
     * Generates an HTML fragment that visualises the graph using modern CSS.
     *
     * Nodes are rendered in insertion order; each edge appears under its source node.
     */
    public function generateHtml(): string
    {
        $html = '<div class="graph">' . PHP_EOL;

        foreach ($this->nodes as $node) {
            $html .= '  <div class="node" id="node-' . htmlspecialchars($node->id, ENT_QUOTES) . '">'
                  . htmlspecialchars($node->label, ENT_QUOTES) . '</div>' . PHP_EOL;

            // Render outgoing edges directly after the source node.
            foreach ($this->edges as $edge) {
                if ($edge->from === $node->id) {
                    $html .= '  <div class="edge" data-from="' . htmlspecialchars($edge->from, ENT_QUOTES)
                          . '" data-to="' . htmlspecialchars($edge->to, ENT_QUOTES) . '">'
                          . htmlspecialchars($edge->label, ENT_QUOTES) . '</div>' . PHP_EOL;
                }
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Returns a full HTML document containing the graph and its CSS.
     */
    public function renderDocument(string $title = 'Graph'): string
    {
        $css = $this->generateCss();
        $body = $this->generateHtml();

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{$title}</title>
<style>
{$css}
</style>
</head>
<body>
{$body}
</body>
</html>
HTML;
    }
}
?>
