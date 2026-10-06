<?php
declare(strict_types=1);

require_once 'core.php';

function assertPathExists(AStarPathfinder $pathfinder, bool $expected): void
{
    $path = $pathfinder->findPath();
    $exists = $path !== null;
    assert($exists === $expected, "Path existence mismatch: expected " . ($expected ? "true" : "false") . ", got " . ($exists ? "true" : "false"));
}

function assertPathLength(AStarPathfinder $pathfinder, int $expectedLength): void
{
    $path = $pathfinder->findPath();
    assert($path !== null, "Path not found for length assertion");
    assert(count($path) === $expectedLength, "Path length mismatch: expected $expectedLength, got " . count($path));
}

function assertPathStartsAndEnds(AStarPathfinder $pathfinder, array $start, array $goal): void
{
    $path = $pathfinder->findPath();
    assert($path !== null, "Path not found for start/end assertion");
    assert($path[0] === $start, "Path does not start at correct position");
    assert(end($path) === $goal, "Path does not end at correct position");
}

function testBasicPathfinding(): void
{
    $pathfinder = new AStarPathfinder(5, 5, [0, 0], [4, 4]);
    assertPathExists($pathfinder, true);
    assertPathLength($pathfinder, 9);
    assertPathStartsAndEnds($pathfinder, [0, 0], [4, 4]);
}

function testObstacleAvoidance(): void
{
    $obstacles = [[2, 2]];
    $pathfinder = new AStarPathfinder(5, 5, [0, 0], [4, 4], $obstacles);
    assertPathExists($pathfinder, true);
    $path = $pathfinder->findPath();
    $hasObstacle = false;
    foreach ($path as $pos) {
        if ($pos === [2, 2]) {
            $hasObstacle = true;
            break;
        }
    }
    assert(!$hasObstacle, "Path should avoid obstacles");
}

function testHighObstacleCost(): void
{
    $obstacles = [[2, 2]];
    $pathfinder = new AStarPathfinder(5, 5, [0, 0], [4, 4], $obstacles, 100.0);
    assertPathExists($pathfinder, true);
    $path = $pathfinder->findPath();
    $hasObstacle = false;
    foreach ($path as $pos) {
        if ($pos === [2, 2]) {
            $hasObstacle = true;
            break;
        }
    }
    assert(!$hasObstacle, "Path should avoid high-cost obstacles");
}

function testNoPathAvailable(): void
{
    $obstacles = [[1, 0], [1, 1], [1, 2], [1, 3], [1, 4]];
    $pathfinder = new AStarPathfinder(5, 5, [0, 0], [4, 4], $obstacles);
    assertPathExists($pathfinder, false);
}

function testStartEqualsGoal(): void
{
    $pathfinder = new AStarPathfinder(5, 5, [2, 2], [2, 2]);
    assertPathExists($pathfinder, true);
    assertPathLength($pathfinder, 1);
}

function testHeuristicWeightEffect(): void
{
    $obstacles = [[2, 2]];
    $pathfinder1 = new AStarPathfinder(5, 5, [0, 0], [4, 4], $obstacles, 10.0, 1.0);
    $pathfinder2 = new AStarPathfinder(5, 5, [0, 0], [4, 4], $obstacles, 10.0, 0.5);
    
    assertPathExists($pathfinder1, true);
    assertPathExists($pathfinder2, true);
}

function testGridBoundaries(): void
{
    $pathfinder = new AStarPathfinder(1, 1, [0, 0], [0, 0]);
    assertPathExists($pathfinder, true);
    assertPathLength($pathfinder, 1);
}

function testComplexObstaclePattern(): void
{
    $obstacles = [
        [1, 1], [1, 2], [1, 3],
        [2, 1], [2, 3],
        [3, 1], [3, 2], [3, 3]
    ];
    $pathfinder = new AStarPathfinder(5, 5, [0, 0], [4, 4], $obstacles);
    assertPathExists($pathfinder, true);
}

function testAllObstacles(): void
{
    $obstacles = [];
    for ($x = 0; $x < 5; $x++) {
        for ($y = 0; $y < 5; $y++) {
            if (!($x === 0 && $y === 0) && !($x === 4 && $y === 4)) {
                $obstacles[] = [$x, $y];
            }
        }
    }
    $pathfinder = new AStarPathfinder(5, 5, [0, 0], [4, 4], $obstacles);
    assertPathExists($pathfinder, false);
}

function testPathContinuity(): void
{
    $pathfinder = new AStarPathfinder(10, 10, [0, 0], [9, 9]);
    $path = $pathfinder->findPath();
    assert($path !== null, "Path not found for continuity test");
    
    for ($i = 1; $i < count($path); $i++) {
        $prev = $path[$i - 1];
        $curr = $path[$i];
        $dx = abs($curr[0] - $prev[0]);
        $dy = abs($curr[1] - $prev[1]);
        assert($dx + $dy === 1, "Path is not continuous at step $i");
    }
}

function main(): void
{
    echo "Running A* Pathfinding Tests...\n";
    
    testBasicPathfinding();
    echo "✓ Basic pathfinding\n";
    
    testObstacleAvoidance();
    echo "✓ Obstacle avoidance\n";
    
    testHighObstacleCost();
    echo "✓ High obstacle cost\n";
    
    testNoPathAvailable();
    echo "✓ No path available\n";
    
    testStartEqualsGoal();
    echo "✓ Start equals goal\n";
    
    testHeuristicWeightEffect();
    echo "✓ Heuristic weight effect\n";
    
    testGridBoundaries();
    echo "✓ Grid boundaries\n";
    
    testComplexObstaclePattern();
    echo "✓ Complex obstacle pattern\n";
    
    testAllObstacles();
    echo "✓ All obstacles\n";
    
    testPathContinuity();
    echo "✓ Path continuity\n";
    
    echo "\nAll tests passed!\n";
}

main();
