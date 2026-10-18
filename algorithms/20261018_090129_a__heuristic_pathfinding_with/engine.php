<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * A* path‑finding with support for per‑cell movement costs (dynamic obstacles).
 *
 * The grid is a rectangular matrix $grid[y][x] where each entry is a non‑negative
 * float representing the cost to ENTER that cell. A cost of INF (PHP_FLOAT_MAX)
 * makes the cell impassable.
 *
 * The algorithm works on 4‑connected movement (up, down, left, right) and uses
 * Manhattan distance as an admissible heuristic.
 */
final class AStar
{
    /** @var array<int, array<int, float>> */
    private array $grid;
    private int $width;
    private int $height;

    public function __construct(array $grid)
    {
        $this->validateGrid($grid);
        $this->grid   = $grid;
        $this->height = count($grid);
        $this->width  = count($grid[0]);
    }

    /**
     * Find the cheapest path from $start to $goal.
     *
     * @return Position[] Empty array if no path exists; otherwise ordered list from start to goal.
     */
    public function findPath(Position $start, Position $goal): array
    {
        // Trivial case.
        if ($start->equals($goal)) {
            return [$start];
        }

        $openSet = new SplPriorityQueue();
        // SplPriorityQueue extracts highest priority first; we store negative f to get a min‑heap.
        $openSet->setExtractFlags(SplPriorityQueue::EXTR_DATA);

        $startNode = new Node($start, 0.0, $this->heuristic($start, $goal));
        $openSet->insert($startNode, -$startNode->f);

        /** @var array<string, float> $gScore  best known g for each position */
        $gScore = [];
        $gScore[$this->key($start)] = 0.0;

        /** @var array<string, Node> $cameFrom  for path reconstruction */
        $cameFrom = [];

        while (!$openSet->isEmpty()) {
            /** @var Node $current */
            $current = $openSet->extract();

            if ($current->pos->equals($goal)) {
                return $this->reconstructPath($current);
            }

            foreach ($this->neighbors($current->pos) as $neighborPos) {
                $tentativeG = $current->g + $this->moveCost($neighborPos);
                $neighborKey = $this->key($neighborPos);

                if (!isset($gScore[$neighborKey]) || $tentativeG < $gScore[$neighborKey]) {
                    $gScore[$neighborKey] = $tentativeG;
                    $f = $tentativeG + $this->heuristic($neighborPos, $goal);
                    $neighborNode = new Node($neighborPos, $tentativeG, $f, $current);
                    $openSet->insert($neighborNode, -$f);
                    $cameFrom[$neighborKey] = $current;
                }
            }
        }

        // No path found.
        return [];
    }

    /** @return Position[] */
    private function neighbors(Position $pos): array
    {
        $dirs = [
            [0, -1], // up
            [0, 1],  // down
            [-1, 0], // left
            [1, 0],  // right
        ];

        $result = [];
        foreach ($dirs as [$dx, $dy]) {
            $nx = $pos->x + $dx;
            $ny = $pos->y + $dy;
            if ($this->inBounds($nx, $ny) && $this->grid[$ny][$nx] < PHP_FLOAT_MAX) {
                $result[] = new Position($nx, $ny);
            }
        }
        return $result;
    }

    private function inBounds(int $x, int $y): bool
    {
        return $x >= 0 && $x < $this->width && $y >= 0 && $y < $this->height;
    }

    /** Manhattan distance – admissible for 4‑connected grids. */
    private function heuristic(Position $a, Position $b): float
    {
        return (float) (abs($a->x - $b->x) + abs($a->y - $b->y));
    }

    /** Cost to ENTER a cell (dynamic obstacle cost). */
    private function moveCost(Position $pos): float
    {
        // Base movement cost is 1; the grid stores additional cost.
        return 1.0 + $this->grid[$pos->y][$pos->x];
    }

    /** Reconstruct path by following parent links. */
    private function reconstructPath(Node $node): array
    {
        $path = [];
        $current = $node;
        while ($current !== null) {
            $path[] = $current->pos;
            $current = $current->parent;
        }
        return array_reverse($path);
    }

    private function key(Position $pos): string
    {
        return $pos->x . ',' . $pos->y;
    }

    /** Validate that the grid is rectangular and contains only non‑negative floats. */
    private function validateGrid(array $grid): void
    {
        if (empty($grid)) {
            throw new InvalidArgumentException('Grid must not be empty.');
        }
        $rowLength = count($grid[0]);
        foreach ($grid as $row) {
            if (!is_array($row) || count($row) !== $rowLength) {
                throw new InvalidArgumentException('All rows must have the same length.');
            }
            foreach ($row as $cell) {
                if (!is_float($cell) && !is_int($cell)) {
                    throw new InvalidArgumentException('Grid cells must be numeric.');
                }
                if ($cell < 0) {
                    throw new InvalidArgumentException('Grid cells must be non‑negative.');
                }
            }
        }
    }
}
