<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Enable assertions
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_EXCEPTION, 1);

// Test data
$sample = "the quick brown fox jumps over the lazy dog";
$compressor = new Compressor($sample);
$bits = $compressor->compress($sample);
$decoded = $compressor->decompress($bits);
assert($decoded === $sample, 'Full round‑trip failed');

// Random access tests
$len = strlen($sample);
for ($i = 0; $i < $len; ++$i) {
    $char = $compressor->getCharAt($bits, $i);
    assert($char === $sample[$i], "Random access mismatch at $i");
}

// Edge cases
$empty = "";
$compressorEmpty = new Compressor($empty);
$bitsEmpty = $compressorEmpty->compress($empty);
assert($bitsEmpty === [], 'Empty string compression');
assert($compressorEmpty->decompress($bitsEmpty) === $empty, 'Empty string decompression');

// Benchmark (simple)
$large = str_repeat($sample, 1000);
$start = microtime(true);
$comp = new Compressor($large);
$bitsLarge = $comp->compress($large);
$decodedLarge = $comp->decompress($bitsLarge);
$elapsed = microtime(true) - $start;
assert($decodedLarge === $large, 'Large data round‑trip');
echo "Benchmark: processed " . strlen($large) . " bytes in " . number_format($elapsed, 4) . " seconds\n";

?>
