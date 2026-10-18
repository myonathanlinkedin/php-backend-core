<?php
declare(strict_types=1);

/**
 * Immutable 2‑D coordinate.
 */
final class Position
{
    public int $x;
    public int $y;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function equals(Position $other): bool
    {
        return $this->x === $other->x && $this->y === $other->y;
    }

    public function __toString(): string
    {
        return sprintf('(%d,%d)', $this->x, $this->y);
    }
}

/**
 * Internal node used by the A* algorithm.
 */
final class Node
{
    public Position $pos;
    public float $g;      // Cost from start to this node.
    public float $f;      // Estimated total cost (g + h).
    public ?Node $parent; // Link to predecessor.

    public function __construct(Position $pos, float $g, float $f, ?Node $parent = null)
    {
        $this->pos    = $pos;
        $this->g      = $g;
        $this->f      = $f;
        $this->parent = $parent;
    }
}
