<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Simple mock compressor that simulates lossy compression.
 *
 * The "compressed" size is proportional to the quality:
 *   size = originalSize * (0.5 + 0.5 * quality/100)
 *
 * Higher quality => larger output.
 */
final class MockCompressor implements CompressorInterface
{
    public function compress(string $data, int $quality): string
    {
        $originalSize = strlen($data);
        $ratio = 0.5 + 0.5 * ($quality / 100);
        $targetSize = (int)ceil($originalSize * $ratio);
        // Return a string of the requested length (filled with 'x')
        return str_repeat('x', $targetSize);
    }
}

/**
 * Core tuning engine that searches for the highest quality that satisfies the target size.
 */
final class TunerEngine
{
    /**
     * Perform a binary‑search based tuning.
     *
     * @param string          $data       Raw input data.
     * @param TunerConfig     $config     Desired constraints.
     * @param CompressorInterface $compressor Compressor implementation.
     *
     * @return TunerResult Result containing the best quality and compressed payload.
     */
    public function tune(string $data, TunerConfig $config, CompressorInterface $compressor): TunerResult
    {
        $low = $config->minQuality;
        $high = $config->maxQuality;
        $bestResult = null;

        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            $compressed = $compressor->compress($data, $mid);
            $size = strlen($compressed);

            if ($size <= $config->targetSize) {
                // This quality works; try higher quality.
                $bestResult = new TunerResult($mid, $compressed);
                $low = $mid + 1;
            } else {
                // Too large; lower quality.
                $high = $mid - 1;
            }
        }

        // If nothing fits, fall back to the minimum quality.
        if ($bestResult === null) {
            $compressed = $compressor->compress($data, $config->minQuality);
            $bestResult = new TunerResult($config->minQuality, $compressed);
        }

        return $bestResult;
    }
}
