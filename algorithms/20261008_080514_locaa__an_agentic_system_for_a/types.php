<?php
declare(strict_types=1);

interface CompressorInterface
{
    /**
     * Compress raw data using a quality setting.
     *
     * @param string $data    The raw input data.
     * @param int    $quality Quality level (0‑100).
     *
     * @return string The compressed data.
     */
    public function compress(string $data, int $quality): string;
}

/**
 * Configuration for the tuner.
 */
final class TunerConfig
{
    /** @var int Desired maximum size in bytes */
    public int $targetSize;

    /** @var int Minimum quality allowed (inclusive) */
    public int $minQuality;

    /** @var int Maximum quality allowed (inclusive) */
    public int $maxQuality;

    public function __construct(int $targetSize, int $minQuality = 0, int $maxQuality = 100)
    {
        $this->targetSize = $targetSize;
        $this->minQuality = max(0, min(100, $minQuality));
        $this->maxQuality = max(0, min(100, $maxQuality));
        if ($this->minQuality > $this->maxQuality) {
            throw new InvalidArgumentException('minQuality cannot be greater than maxQuality');
        }
    }
}

/**
 * Result of a tuning operation.
 */
final class TunerResult
{
    /** @var int Quality that achieved the result */
    public int $quality;

    /** @var string Compressed payload */
    public string $compressedData;

    /** @var int Actual size of the compressed payload */
    public int $actualSize;

    public function __construct(int $quality, string $compressedData)
    {
        $this->quality = $quality;
        $this->compressedData = $compressedData;
        $this->actualSize = strlen($compressedData);
    }
}
