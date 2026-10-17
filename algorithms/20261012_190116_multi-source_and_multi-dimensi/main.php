<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Ensure assertions abort on failure
assert_options(ASSERT_BAIL, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_CALLBACK, static function (int $file, int $line, string $code, string $desc = null) {
    throw new AssertionError("Assertion failed at {$file}:{$line} - {$code}");
});

/**
 * Helper to compare two float vectors with tolerance.
 */
function assertVectorsEqual(array $expected, array $actual, float $epsilon = 1e-6): void
{
    ksort($expected);
    ksort($actual);
    assert(count($expected) === count($actual), 'Vector dimension count mismatch');
    foreach ($expected as $dim => $expVal) {
        assert(array_key_exists($dim, $actual), "Missing dimension '{$dim}' in actual");
        $actVal = $actual[$dim];
        assert(abs($expVal - $actVal) <= $epsilon, "Dimension '{$dim}' mismatch: expected {$expVal}, got {$actVal}");
    }
}

/* ---------- Unit Tests ---------- */

function testEmptyEngine(): void
{
    $engine = new FusionEngine();
    $fusion = $engine->getFusion();
    assert($fusion === [], 'Fusion of empty engine should be empty array');
}
testEmptyEngine();

function testSingleSourceSingleDimension(): void
{
    $engine = new FusionEngine(3);
    $engine->addSource('sensorA', ['temp' => 1.0]);
    $engine->ingest('sensorA', ['temp' => 20.0]);
    $engine->ingest('sensorA', ['temp' => 22.0]);
    $engine->ingest('sensorA', ['temp' => 24.0]);

    $fusion = $engine->getFusion();
    // Mean of 20,22,24 = 22
    assertVectorsEqual(['temp' => 22.0], $fusion);
}
testSingleSourceSingleDimension();

function testMultipleSourcesMultipleDimensions(): void
{
    $engine = new FusionEngine(4);
    $engine->addSource('cam', ['x' => 0.6, 'y' => 0.6]);
    $engine->addSource('lidar', ['x' => 0.4, 'y' => 0.4]);

    // Camera provides two samples
    $engine->ingest('cam', ['x' => 5.0, 'y' => 10.0]);
    $engine->ingest('cam', ['x' => 7.0, 'y' => 14.0]);

    // Lidar provides three samples
    $engine->ingest('lidar', ['x' => 6.0, 'y' => 9.0]);
    $engine->ingest('lidar', ['x' => 8.0, 'y' => 12.0]);
    $engine->ingest('lidar', ['x' => 10.0, 'y' => 15.0]);

    // Compute expected means per source
    // cam mean: x=(5+7)/2=6, y=(10+14)/2=12
    // lidar mean: x=(6+8+10)/3=8, y=(9+12+15)/3=12
    // fused x = (6*0.6 + 8*0.4) / (0.6+0.4) = (3.6+3.2)/1 = 6.8
    // fused y = (12*0.6 + 12*0.4) / 1 = 12
    $expected = ['x' => 6.8, 'y' => 12.0];
    $fusion = $engine->getFusion();
    assertVectorsEqual($expected, $fusion);
}
testMultipleSourcesMultipleDimensions();

function testBufferOverflow(): void
{
    $engine = new FusionEngine(2); // capacity 2
    $engine->addSource('sensorB', ['a' => 1.0]);

    $engine->ingest('sensorB', ['a' => 1.0]); // first
    $engine->ingest('sensorB', ['a' => 2.0]); // second
    assert($engine->getBufferSize('sensorB') === 2, 'Buffer should be full');

    $engine->ingest('sensorB', ['a' => 3.0]); // overflow, oldest dropped
    assert($engine->getBufferSize('sensorB') === 2, 'Buffer size remains at capacity');

    // Buffer now contains [2.0, 3.0] -> mean = 2.5
    $fusion = $engine->getFusion();
    assertVectorsEqual(['a' => 2.5], $fusion);
}
testBufferOverflow();

function testMissingDimensions(): void
{
    $engine = new FusionEngine(3);
    $engine->addSource('src1', ['p' => 1.0, 'q' => 1.0]);
    $engine->addSource('src2', ['p' => 1.0, 'r' => 1.0]);

    $engine->ingest('src1', ['p' => 10.0, 'q' => 20.0]);
    $engine->ingest('src2', ['p' => 30.0, 'r' => 40.0]);

    // src1 mean: p=10, q=20
    // src2 mean: p=30, r=40
    // fused p = (10*1 + 30*1) / (1+1) = 20
    // fused q = (20*1) / 1 = 20
    // fused r = (40*1) / 1 = 40
    $expected = ['p' => 20.0, 'q' => 20.0, 'r' => 40.0];
    $fusion = $engine->getFusion();
    assertVectorsEqual($expected, $fusion);
}
testMissingDimensions();

function testResetFunctionality(): void
{
    $engine = new FusionEngine();
    $engine->addSource('s', ['x' => 1.0]);
    $engine->ingest('s', ['x' => 5.0]);
    $fusion1 = $engine->getFusion();
    assertVectorsEqual(['x' => 5.0], $fusion1);

    $engine->reset();
    $fusion2 = $engine->getFusion();
    assert($fusion2 === [], 'After reset, fusion should be empty');
}
testResetFunctionality();

echo "All tests passed.\n";
