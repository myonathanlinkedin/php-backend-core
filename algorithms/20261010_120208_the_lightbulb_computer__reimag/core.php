<?php
declare(strict_types=1);

/**
 * Simple 2D point representation.
 */
final class Point
{
    public float $x;
    public float $y;

    public function __construct(float $x, float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Euclidean distance squared to another point.
     */
    public function distanceSquared(Point $other): float
    {
        $dx = $this->x - $other->x;
        $dy = $this->y - $other->y;
        return $dx * $dx + $dy * $dy;
    }
}

/**
 * Axis-aligned rectangle used for quadtree bounds and queries.
 */
final class Rectangle
{
    public float $x; // center x
    public float $y; // center y
    public float $halfWidth;
    public float $halfHeight;

    public function __construct(float $x, float $y, float $halfWidth, float $halfHeight)
    {
        $this->x = $x;
        $this->y = $y;
        $this->halfWidth = $halfWidth;
        $this->halfHeight = $halfHeight;
    }

    /**
     * Checks whether a point lies within (or on the edge of) the rectangle.
     */
    public function contains(Point $p): bool
    {
        return ($p->x >= $this->x - $this->halfWidth) &&
               ($p->x <= $this->x + $this->halfWidth) &&
               ($p->y >= $this->y - $this->halfHeight) &&
               ($p->y <= $this->y + $this->halfHeight);
    }

    /**
     * Checks whether this rectangle intersects another rectangle.
     */
    public function intersects(Rectangle $other): bool
    {
        return !(
            $other->x - $other->halfWidth > $this->x + $this->halfWidth ||
            $other->x + $other->halfWidth < $this->x - $this->halfWidth ||
            $other->y - $other->halfHeight > $this->y + $this->halfHeight ||
            $other->y + $other->halfHeight < $this->y - $this->halfHeight
        );
    }
}

/**
 * Quadtree node for spatial indexing of lightbulb positions.
 */
final class QuadtreeNode
{
    private Rectangle $boundary;
    private int $capacity;
    /** @var Point[] */
    private array $points = [];
    private bool $divided = false;
    private ?QuadtreeNode $northEast = null;
    private ?QuadtreeNode $northWest = null;
    private ?QuadtreeNode $southEast = null;
    private ?QuadtreeNode $southWest = null;

    public function __construct(Rectangle $boundary, int $capacity = 4)
    {
        $this->boundary = $boundary;
        $this->capacity = $capacity;
    }

    /**
     * Insert a point into the quadtree.
     *
     * @return bool true if insertion succeeded.
     */
    public function insert(Point $p): bool
    {
        if (!$this->boundary->contains($p)) {
            return false; // out of bounds
        }

        if (count($this->points) < $this->capacity) {
            $this->points[] = $p;
            return true;
        }

        if (!$this->divided) {
            $this->subdivide();
        }

        // Try inserting into children
        return $this->northEast->insert($p) ||
               $this->northWest->insert($p) ||
               $this->southEast->insert($p) ||
               $this->southWest->insert($p);
    }

    /**
     * Subdivide this node into four children.
     */
    private function subdivide(): void
    {
        $x = $this->boundary->x;
        $y = $this->boundary->y;
        $hw = $this->boundary->halfWidth / 2.0;
        $hh = $this->boundary->halfHeight / 2.0;

        $ne = new Rectangle($x + $hw, $y - $hh, $hw, $hh);
        $nw = new Rectangle($x - $hw, $y - $hh, $hw, $hh);
        $se = new Rectangle($x + $hw, $y + $hh, $hw, $hh);
        $sw = new Rectangle($x - $hw, $y + $hh, $hw, $hh);

        $this->northEast = new QuadtreeNode($ne, $this->capacity);
        $this->northWest = new QuadtreeNode($nw, $this->capacity);
        $this->southEast = new QuadtreeNode($se, $this->capacity);
        $this->southWest = new QuadtreeNode($sw, $this->capacity);

        $this->divided = true;
    }

    /**
     * Retrieve all points within a query rectangle.
     *
     * @param Rectangle $range
     * @param Point[] $found (output parameter)
     */
    public function queryRange(Rectangle $range, array &$found): void
    {
        if (!$this->boundary->intersects($range)) {
            return; // no intersection
        }

        foreach ($this->points as $p) {
            if ($range->contains($p)) {
                $found[] = $p;
            }
        }

        if ($this->divided) {
            $this->northEast->queryRange($range, $found);
            $this->northWest->queryRange($range, $found);
            $this->southEast->queryRange($range, $found);
            $this->southWest->queryRange($range, $found);
        }
    }

    /**
     * Find the nearest point to a target point.
     *
     * @param Point $target
     * @param Point|null $best (output) current best candidate
     * @param float $bestDistSq (output) squared distance of best candidate
     */
    public function nearest(Point $target, ?Point &$best, float &$bestDistSq): void
    {
        // Compute distance from target to this node's boundary (as a rectangle)
        $dx = max(0.0, abs($target->x - $this->boundary->x) - $this->boundary->halfWidth);
        $dy = max(0.0, abs($target->y - $this->boundary->y) - $this->boundary->halfHeight);
        $distSqBoundary = $dx * $dx + $dy * $dy;

        if ($distSqBoundary > $bestDistSq) {
            // This node cannot contain a closer point.
            return;
        }

        foreach ($this->points as $p) {
            $d = $target->distanceSquared($p);
            if ($d < $bestDistSq) {
                $bestDistSq = $d;
                $best = $p;
            }
        }

        if ($this->divided) {
            // Visit children in order of proximity to improve pruning.
            $children = [
                $this->northEast,
                $this->northWest,
                $this->southEast,
                $this->southWest,
            ];
            usort($children, function (QuadtreeNode $a, QuadtreeNode $b) use ($target): int {
                $dxA = max(0.0, abs($target->x - $a->boundary->x) - $a->boundary->halfWidth);
                $dyA = max(0.0, abs($target->y - $a->boundary->y) - $a->boundary->halfHeight);
                $distA = $dxA * $dxA + $dyA * $dyA;

                $dxB = max(0.0, abs($target->x - $b->boundary->x) - $b->boundary->halfWidth);
                $dyB = max(0.0, abs($target->y - $b->boundary->y) - $b->boundary->halfHeight);
                $distB = $dxB * $dxB + $dyB * $dyB;

                return $distA <=> $distB;
            });

            foreach ($children as $child) {
                $child->nearest($target, $best, $bestDistSq);
            }
        }
    }
}

/**
 * Public façade for managing lightbulb positions.
 */
final class LightbulbSpace
{
    private QuadtreeNode $root;

    /**
     * @param Rectangle $worldBounds defines the total spatial domain.
     * @param int $capacity per node before subdivision.
     */
    public function __construct(Rectangle $worldBounds, int $capacity = 4)
    {
        $this->root = new QuadtreeNode($worldBounds, $capacity);
    }

    public function addLightbulb(Point $p): bool
    {
        return $this->root->insert($p);
    }

    /**
     * @return Point[] points inside the given rectangle.
     */
    public function query(Rectangle $range): array
    {
        $found = [];
        $this->root->queryRange($range, $found);
        return $found;
    }

    /**
     * Returns the nearest lightbulb to $target or null if space is empty.
     */
    public function nearestLightbulb(Point $target): ?Point
    {
        $best = null;
        $bestDistSq = INF;
        $this->root->nearest($target, $best, $bestDistSq);
        return $best;
    }
}
