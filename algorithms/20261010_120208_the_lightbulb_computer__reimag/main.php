<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Enable strict assertions.
ini_set('assert.exception', '1');
ini_set('assert.active', '1');

/**
 * Helper to compare two points with tolerance.
 */
function pointsEqual(Point $a, Point $b, float $epsilon = 1e-6): bool
{
    return abs($a->x - $b->x) < $epsilon && abs($a->y - $b->y) < $epsilon;
}

/* ---------- Unit Tests ---------- */

// Test 1: Basic insertion and capacity handling.
$space = new LightbulbSpace(new Rectangle(0.0, 0.0, 10.0, 10.0), 2);
assert($space->addLightbulb(new Point(1.0, 1.0)) === true);
assert($space->addLightbulb(new Point(-1.0, -1.0)) === true);
assert($space->addLightbulb(new Point(2.0, 2.0)) === true); // forces subdivision

// Test 2: Range query returns correct points.
$range = new Rectangle(0.0, 0.0, 1.5, 1.5);
$found = $space->query($range);
assert(count($found) === 2);
$expected = [new Point(1.0, 1.0), new Point(-1.0, -1.0)];
foreach ($expected as $exp) {
    $match = false;
    foreach ($found as $f) {
        if (pointsEqual($exp, $f)) {
            $match = true;
            break;
        }
    }
    assert($match === true);
}

// Test 3: Nearest neighbor works for interior point.
$target = new Point(0.9, 0.9);
$nearest = $space->nearestLightbulb($target);
assert($nearest !== null);
assert(pointsEqual($nearest, new Point(1.0, 1.0)));

// Test 4: Nearest neighbor when target outside all points.
$target2 = new Point(9.0, 9.0);
$nearest2 = $space->nearestLightbulb($target2);
assert($nearest2 !== null);
assert(pointsEqual($nearest2, new Point(2.0, 2.0)));

// Test 5: Edge case – duplicate points.
assert($space->addLightbulb(new Point(1.0, 1.0)) === true);
$dupQuery = $space->query(new Rectangle(1.0, 1.0, 0.0, 0.0));
assert(count($dupQuery) === 2); // both original and duplicate present

// Test 6: Empty space nearest returns null.
$emptySpace = new LightbulbSpace(new Rectangle(0.0, 0.0, 5.0, 5.0));
assert($emptySpace->nearestLightbulb(new Point(0.0, 0.0)) === null);

// Test 7: Points on boundary are considered inside.
$boundarySpace = new LightbulbSpace(new Rectangle(0.0, 0.0, 5.0, 5.0));
assert($boundarySpace->addLightbulb(new Point(5.0, 0.0)) === true);
$boundaryQuery = $boundarySpace->query(new Rectangle(5.0, 0.0, 0.0, 0.0));
assert(count($boundaryQuery) === 1);
assert(pointsEqual($boundaryQuery[0], new Point(5.0, 0.0)));

/* ---------- Simple Benchmark (optional) ---------- */
function benchmarkInsertion(int $n): void
{
    $space = new LightbulbSpace(new Rectangle(0.0, 0.0, 1000.0, 1000.0), 4);
    $start = microtime(true);
    for ($i = 0; $i < $n; ++$i) {
        $x = mt_rand(-100000, 100000) / 100.0;
        $y = mt_rand(-100000, 100000) / 100.0;
        $space->addLightbulb(new Point($x, $y));
    }
    $elapsed = microtime(true) - $start;
    echo "Inserted {$n} points in " . round($elapsed, 4) . " seconds.\n";
}

// Run a modest benchmark when executed directly.
if (php_sapi_name() === 'cli') {
    benchmarkInsertion(10000);
}
?>
