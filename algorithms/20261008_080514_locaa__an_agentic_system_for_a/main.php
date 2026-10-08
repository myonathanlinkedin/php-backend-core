<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

// Helper to run a single assertion with a message.
function expect(bool $condition, string $message): void
{
    assert($condition, $message);
}

// Sample raw data (simulated image bytes).
$rawData = str_repeat('A', 1000); // 1000 bytes

$compressor = new MockCompressor();
$engine = new TunerEngine();

/* -------------------------------------------------
 * Test 1: Target size that can be met with high quality.
 * ------------------------------------------------- */
$config1 = new TunerConfig(targetSize: 800, minQuality: 0, maxQuality: 100);
$result1 = $engine->tune($rawData, $config1, $compressor);
expect($result1->actualSize <= $config1->targetSize, 'Result size exceeds target in Test 1');
expect($result1->quality >= $config1->minQuality && $result1->quality <= $config1->maxQuality, 'Quality out of bounds in Test 1');

/* -------------------------------------------------
 * Test 2: Very small target size forces low quality.
 * ------------------------------------------------- */
$config2 = new TunerConfig(targetSize: 300, minQuality: 0, maxQuality: 100);
$result2 = $engine->tune($rawData, $config2, $compressor);
expect($result2->actualSize <= $config2->targetSize, 'Result size exceeds target in Test 2');
expect($result2->quality >= $config2->minQuality && $result2->quality <= $config2->maxQuality, 'Quality out of bounds in Test 2');

/* -------------------------------------------------
 * Test 3: Target larger than original data – should return max quality.
 * ------------------------------------------------- */
$config3 = new TunerConfig(targetSize: 2000, minQuality: 0, maxQuality: 100);
$result3 = $engine->tune($rawData, $config3, $compressor);
expect($result3->quality === $config3->maxQuality, 'Did not select max quality when target exceeds original size');
expect($result3->actualSize <= $config3->targetSize, 'Result size exceeds target in Test 3');

/* -------------------------------------------------
 * Test 4: Target smaller than the smallest possible compressed size.
 * ------------------------------------------------- */
$config4 = new TunerConfig(targetSize: 100, minQuality: 0, maxQuality: 100);
$result4 = $engine->tune($rawData, $config4, $compressor);
expect($result4->quality === $config4->minQuality, 'Did not fall back to min quality when target impossible');
expect($result4->actualSize > $config4->targetSize, 'Unexpectedly met impossible target size in Test 4');

/* -------------------------------------------------
 * Demo output (optional, not part of assertions)
 * ------------------------------------------------- */
echo "Test suite completed successfully.\n";
echo "Sample result (Test 1): Quality {$result1->quality}, Size {$result1->actualSize} bytes.\n";
