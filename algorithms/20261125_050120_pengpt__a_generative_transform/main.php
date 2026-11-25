<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

/**
 * Helper to compare two arrays of Point with tolerance.
 */
function assertPointsEqual(array $actual, array $expected, float $epsilon = 1e-6): void
{
    assert(count($actual) === count($expected), 'Point count mismatch');
    $n = count($actual);
    for ($i = 0; $i < $n; ++$i) {
        $a = $actual[$i];
        $e = $expected[$i];
        assert($a instanceof Point && $e instanceof Point, 'Elements must be Point');
        $dx = abs($a->x - $e->x);
        $dy = abs($a->y - $e->y);
        assert($dx <= $epsilon && $dy <= $epsilon,
            sprintf('Point %d differs: (%.6f, %.6f) vs (%.6f, %.6f)', $i, $a->x, $a->y, $e->x, $e->y));
    }
}

/**
 * Unit tests for the PenGenerator/Turtle implementation.
 */
function runTests(): void
{
    // Test 1: single forward move
    $gen = new PenGenerator(step: 1.0, angleDeg: 90.0);
    $strokes = $gen->generate('F');
    assert(count($strokes) === 1, 'Expected one stroke');
    $pts = $strokes[0]->getPoints();
    $expected = [
        new Point(0.0, 0.0),
        new Point(1.0, 0.0),
    ];
    assertPointsEqual($pts, $expected);

    // Test 2: forward, turn right, forward
    $strokes = $gen->generate('F+F');
    $pts = $strokes[0]->getPoints();
    $expected = [
        new Point(0.0, 0.0),
        new Point(1.0, 0.0),          // first F
        new Point(1.0, -1.0),         // after turning right 90°, move down
    ];
    assertPointsEqual($pts, $expected);

    // Test 3: left turn
    $strokes = $gen->generate('F-F');
    $pts = $strokes[0]->getPoints();
    $expected = [
        new Point(0.0, 0.0),
        new Point(1.0, 0.0),          // first F
        new Point(1.0, 1.0),          // turn left, move up
    ];
    assertPointsEqual($pts, $expected);

    // Test 4: branching with push/pop
    $strokes = $gen->generate('F[+F]F');
    $pts = $strokes[0]->getPoints();
    $expected = [
        new Point(0.0, 0.0),          // start
        new Point(1.0, 0.0),          // first F
        // push state (1,0,0)
        // turn right, forward
        new Point(1.0, -1.0),
        // pop state returns to (1,0,0)
        new Point(1.0, 0.0),
        // final F
        new Point(2.0, 0.0),
    ];
    assertPointsEqual($pts, $expected);

    // Test 5: multiple independent strokes separated by whitespace
    $strokes = $gen->generate('F   F+F');
    assert(count($strokes) === 2, 'Expected two strokes');
    $pts0 = $strokes[0]->getPoints();
    $pts1 = $strokes[1]->getPoints();
    $expected0 = [
        new Point(0.0, 0.0),
        new Point(1.0, 0.0),
    ];
    $expected1 = [
        new Point(0.0, 0.0),
        new Point(1.0, 0.0),
        new Point(1.0, -1.0),
    ];
    assertPointsEqual($pts0, $expected0);
    assertPointsEqual($pts1, $expected1);

    // Test 6: empty seed yields empty array
    $strokes = $gen->generate('   ');
    assert(count($strokes) === 0, 'Empty seed should produce no strokes');

    // Test 7: unknown symbols are ignored
    $strokes = $gen->generate('FX+YF');
    $pts = $strokes[0]->getPoints();
    $expected = [
        new Point(0.0, 0.0),
        new Point(1.0, 0.0),          // F
        // X ignored
        // + turn right
        // Y ignored
        new Point(1.0, -1.0),         // final F
    ];
    assertPointsEqual($pts, $expected);
}

// Execute tests when script is run directly.
if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    runTests();
    echo "All tests passed.\n";
}
?>
