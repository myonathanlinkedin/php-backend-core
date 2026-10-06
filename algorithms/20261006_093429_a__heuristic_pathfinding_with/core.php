<?php
declare(strict_types=1);

class AStarPathfinder
{
    private int $width;
    private int $height;
    private array $grid;
    private array $start;
    private array $goal;
    private array $obstacles;
    private float $obstacleCost;
    private float $heuristicWeight;

    public function __construct(
        int $width,
        int $height,
        array $start,
        array $goal,
        array $obstacles = [],
        float $obstacleCost = 10.0,
        float $heuristicWeight = 1.0
    ) {
        $this->width = $width;
        $this->height = $height;
        $this->start = $start;
        $this->goal = $goal;
        $this->obstacles = $obstacles;
        $this->obstacleCost = $obstacleCost;
        $this->heuristicWeight = $heuristicWeight;
        $this->grid = array_fill(0, $height, array_fill(0, $width, 0));
    }

    private function inBounds(int $x, int $y): bool
    {
        return $x >= 0 && $x < $this->width && $y >= 0 && $y < $this->height;
    }

    private function isObstacle(int $x, int $y): bool
    {
        return in_array([$x, $y], $this->obstacles, true);
    }

    private function heuristic(int $x, int $y): float
    {
        $dx = abs($x - $this->goal[0]);
        $dy = abs($y - $this->goal[1]);
        return $this->heuristicWeight * ($dx + $dy);
    }

    private function getNeighbors(int $x, int $y): array
    {
        $neighbors = [];
        $directions = [
            [0, -1], [1, 0], [0, 1], [-1, 0]
        ];
        foreach ($directions as $dir) {
            $nx = $x + $dir[0];
            $ny = $y + $dir[1];
            if ($this->inBounds($nx, $ny)) {
                $cost = $this->isObstacle($nx, $ny) ? $this->obstacleCost : 1.0;
                $neighbors[] = [$nx, $ny, $cost];
            }
        }
        return $neighbors;
    }

    public function findPath(): ?array
    {
        $openSet = new SplPriorityQueue();
        $openSet->setExtractFlags(SplPriorityQueue::EXTR_DATA);
        
        $gScore = [];
        $fScore = [];
        $cameFrom = [];
        
        $startKey = $this->start[0] . ',' . $this->start[1];
        $gScore[$startKey] = 0.0;
        $fScore[$startKey] = $this->heuristic($this->start[0], $this->start[1]);
        $openSet->insert($this->start, -$fScore[$startKey]);
        
        $visited = [];
        
        while (!$openSet->isEmpty()) {
            $current = $openSet->extract();
            $currentKey = $current[0] . ',' . $current[1];
            
            if ($visited[$currentKey] ?? false) {
                continue;
            }
            $visited[$currentKey] = true;
            
            if ($current[0] === $this->goal[0] && $current[1] === $this->goal[1]) {
                return $this->reconstructPath($cameFrom, $current);
            }
            
            $neighbors = $this->getNeighbors($current[0], $current[1]);
            foreach ($neighbors as $neighbor) {
                $nx = $neighbor[0];
                $ny = $neighbor[1];
                $stepCost = $neighbor[2];
                $neighborKey = $nx . ',' . $ny;
                
                if ($visited[$neighborKey] ?? false) {
                    continue;
                }
                
                $tentativeG = $gScore[$currentKey] + $stepCost;
                
                if (!isset($gScore[$neighborKey]) || $tentativeG < $gScore[$neighborKey]) {
                    $cameFrom[$neighborKey] = $current;
                    $gScore[$neighborKey] = $tentativeG;
                    $fScore[$neighborKey] = $tentativeG + $this->heuristic($nx, $ny);
                    $openSet->insert([$nx, $ny], -$fScore[$neighborKey]);
                }
            }
        }
        
        return null;
    }

    private function reconstructPath(array $cameFrom, array $current): array
    {
        $path = [$current];
        $currentKey = $current[0] . ',' . $current[1];
        
        while (isset($cameFrom[$currentKey])) {
            $prev = $cameFrom[$currentKey];
            $path[] = $prev;
            $currentKey = $prev[0] . ',' . $prev[1];
        }
        
        return array_reverse($path);
    }
}
