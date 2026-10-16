<?php
declare(strict_types=1);

/**
 * Simple hyperbolic curve representation.
 *
 * Standard form: x^2 / a^2 - y^2 / b^2 = 1
 * Parametric form: x = a * cosh(t), y = b * sinh(t)
 */
final class HyperbolicCurve
{
    private float $a;
    private float $b;

    public function __construct(float $a, float $b)
    {
        if ($a <= 0.0 || $b <= 0.0) {
            throw new InvalidArgumentException('Parameters a and b must be positive.');
        }
        $this->a = $a;
        $this->b = $b;
    }

    /**
     * Compute y for a given x on the right branch (positive y).
     *
     * @throws InvalidArgumentException if |x| < a (outside the curve)
     */
    public function getY(float $x): float
    {
        $ratio = $x / $this->a;
        if (abs($ratio) < 1.0) {
            throw new InvalidArgumentException('Absolute value of x must be >= a.');
        }
        return $this->b * sqrt($ratio * $ratio - 1.0);
    }

    /**
     * Compute x for a given y (positive x branch).
     *
     * @throws InvalidArgumentException if |y| < 0 (always false, kept for symmetry)
     */
    public function getX(float $y): float
    {
        $ratio = $y / $this->b;
        return $this->a * sqrt($ratio * $ratio + 1.0);
    }

    /**
     * Parametric point for parameter t.
     *
     * @return array{float,float} [x, y]
     */
    public function getPoint(float $t): array
    {
        $x = $this->a * cosh($t);
        $y = $this->b * sinh($t);
        return [$x, $y];
    }
}

/**
 * Exception type for DAG errors.
 */
class DAGException extends Exception {}

/**
 * Node inside a Directed Acyclic Graph.
 */
final class DAGNode
{
    public string $id;
    /** @var callable */
    public $compute;
    /** @var string[] */
    public array $dependencies;
    private bool $evaluated = false;
    private mixed $value = null;

    /**
     * @param string   $id
     * @param callable $compute        Function that returns the node's value.
     * @param string[] $dependencies   List of node ids this node depends on.
     */
    public function __construct(string $id, callable $compute, array $dependencies = [])
    {
        $this->id = $id;
        $this->compute = $compute;
        $this->dependencies = $dependencies;
    }

    public function isEvaluated(): bool
    {
        return $this->evaluated;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
        $this->evaluated = true;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}

/**
 * Directed Acyclic Graph with lazy evaluation.
 */
final class DAG
{
    /** @var array<string, DAGNode> */
    private array $nodes = [];

    /**
     * Add a node to the graph.
     *
     * @throws DAGException if a node with the same id already exists.
     */
    public function addNode(string $id, callable $compute, array $dependencies = []): void
    {
        if (isset($this->nodes[$id])) {
            throw new DAGException("Node '{$id}' already exists.");
        }
        $this->nodes[$id] = new DAGNode($id, $compute, $dependencies);
    }

    /**
     * Evaluate a single node and return its value.
     *
     * @throws DAGException on missing node or cyclic dependency.
     */
    public function evaluate(string $id): mixed
    {
        if (!isset($this->nodes[$id])) {
            throw new DAGException("Node '{$id}' not found.");
        }
        $order = $this->topologicalSort($id);
        foreach ($order as $nodeId) {
            $node = $this->nodes[$nodeId];
            if ($node->isEvaluated()) {
                continue;
            }
            $args = [];
            foreach ($node->dependencies as $depId) {
                $args[] = $this->nodes[$depId]->getValue();
            }
            $value = ($node->compute)(...$args);
            $node->setValue($value);
        }
        return $this->nodes[$id]->getValue();
    }

    /**
     * Evaluate all nodes reachable from the given start node.
     *
     * @return array<string,mixed> Mapping id => value.
     */
    public function evaluateAll(string $startId): array
    {
        $order = $this->topologicalSort($startId);
        foreach ($order as $nodeId) {
            $node = $this->nodes[$nodeId];
            if ($node->isEvaluated()) {
                continue;
            }
            $args = [];
            foreach ($node->dependencies as $depId) {
                $args[] = $this->nodes[$depId]->getValue();
            }
            $value = ($node->compute)(...$args);
            $node->setValue($value);
        }
        $result = [];
        foreach ($order as $nodeId) {
            $result[$nodeId] = $this->nodes[$nodeId]->getValue();
        }
        return $result;
    }

    /**
     * Perform a DFS based topological sort starting from $startId.
     *
     * @return string[] Ordered list of node ids.
     *
     * @throws DAGException on cycle detection or missing dependency.
     */
    private function topologicalSort(string $startId): array
    {
        $WHITE = 0; $GRAY = 1; $BLACK = 2;
        $color = [];
        $order = [];

        $visit = function (string $nodeId) use (&$visit, &$color, &$order, $WHITE, $GRAY, $BLACK): void {
            $color[$nodeId] = $GRAY;
            $node = $this->nodes[$nodeId] ?? null;
            if ($node === null) {
                throw new DAGException("Dependency '{$nodeId}' does not exist.");
            }
            foreach ($node->dependencies as $depId) {
                $c = $color[$depId] ?? $WHITE;
                if ($c === $GRAY) {
                    throw new DAGException('Cycle detected involving node ' . $depId);
                }
                if ($c === $WHITE) {
                    $visit($depId);
                }
            }
            $color[$nodeId] = $BLACK;
            $order[] = $nodeId;
        };

        $visit($startId);
        return array_reverse($order);
    }
}
