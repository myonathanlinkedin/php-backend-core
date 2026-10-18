<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

/**
 * Helper: compare two paths element‑wise.
 */
function pathsEqual(array $a, array $b): bool
{
    if (count($a) !== count($b)) {
        return false;
    }
    foreach ($a as $i => $posA) {
        /** @var Position $posA */
        $posB = $b[$i];
        if (!$posA->equals($posB)) {
            return false;
        }
    }
    return true;
}

/**
 * Helper: pretty‑print a path for debugging (used only in assertions).
 */
function pathToString(array $path): string
{
    return implode(' -> ', array_map(fn(Position $p) => (string) $p, $path));
}

/* -------------------- Unit Tests -------------------- */

// Test 1: Simple 3×3 empty grid – shortest Manhattan path.
$grid1 = [
    [0.0, 0.0, 0.0],
    [0.0, 0.0, 0.0],
    [0.0, 0.0, 0.0],
];
$astar1 = new AStar($grid1);
$start1 = new Position(0, 0);
$goal1  = new Position(2, 2);
$path1  = $astar1->findPath($start1, $goal1);
$expected1 = [
    new Position(0, 0),
    new Position(1, 0),
    new Position(2, 0),
    new Position(2, 1),
    new Position(2, 2),
];
assert(pathsEqual($path1, $expected1), 'Test 1 failed: ' . pathToString($path1));

// Test 2: Dynamic obstacle – high cost cell should be avoided.
$inf = PHP_FLOAT_MAX;
$grid2 = [
    [0.0, 0.0, 0.0, 0.0],
    [0.0, 10.0, 10.0, 0.0], // cells (1,1) and (2,1) have high additional cost.
    [0.0, 0.0, 0.0, 0.0],
];
$astar2 = new AStar($grid2);
$start2 = new Position(0, 1);
$goal2  = new Position(3, 1);
$path2  = $astar2->findPath($start2, $goal2);
$expected2 = [
    new Position(0, 1),
    new Position(0, 0),
    new Position(1, 0),
    new Position(2, 0),
    new Position(3, 0),
    new Position(3, 1),
];
assert(pathsEqual($path2, $expected2), 'Test 2 failed: ' . pathToString($path2));

// Test 3: Start equals goal.
$grid3 = [[0.0]];
$astar3 = new AStar($grid3);
$start3 = new Position(0, 0);
$goal3  = new Position(0, 0);
$path3  = $astar3->findPath($start3, $goal3);
$expected3 = [new Position(0, 0)];
assert(pathsEqual($path3, $expected3), 'Test 3 failed');

// Test 4: Unreachable goal (blocked by impassable cells).
$grid4 = [
    [0.0, $inf, 0.0],
    [0.0, $inf, 0.0],
    [0.0, $inf, 0.0],
];
$astar4 = new AStar($grid4);
$start4 = new Position(0, 0);
$goal4  = new Position(2, 2);
$path4  = $astar4->findPath($start4, $goal4);
assert($path4 === [], 'Test 4 failed – expected empty path');

// Test 5: Larger grid with mixed costs – verify total cost minimality.
$grid5 = [
    [0.0, 0.0, 5.0, 0.0],
    [0.0, 5.0, 5.0, 0.0],
    [0.0, 0.0, 0.0, 0.0],
];
$astar5 = new AStar($grid5);
$start5 = new Position(0, 0);
$goal5  = new Position(3, 2);
$path5  = $astar5->findPath($start5, $goal5);
$expected5 = [
    new Position(0, 0),
    new Position(0, 1),
    new Position(0, 2),
    new Position(1, 2),
    new Position(2, 2),
    new Position(3, 2),
];
assert(pathsEqual($path5, $expected5), 'Test 5 failed: ' . pathToString($path5));

echo "All A* unit tests passed.\n";

?>
