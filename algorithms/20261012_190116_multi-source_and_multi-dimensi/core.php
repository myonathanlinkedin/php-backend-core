<?php
declare(strict_types=1);

/**
 * Fixed-size circular buffer for storing recent vectors.
 */
final class CircularBuffer
{
    /** @var array<int, array<string, float>> */
    private array $buffer = [];
    private int $capacity;
    private int $head = 0;
    private int $size = 0;

    public function __construct(int $capacity)
    {
        if ($capacity <= 0) {
            throw new InvalidArgumentException('Capacity must be positive');
        }
        $this->capacity = $capacity;
    }

    /**
     * Add a new vector to the buffer.
     *
     * @param array<string, float> $vector
     */
    public function push(array $vector): void
    {
        $this->buffer[$this->head] = $vector;
        $this->head = ($this->head + 1) % $this->capacity;
        if ($this->size < $this->capacity) {
            $this->size++;
        }
    }

    /**
     * @return array<int, array<string, float>>
     */
    public function getAll(): array
    {
        $result = [];
        for ($i = 0; $i < $this->size; $i++) {
            $index = ($this->head - $this->size + $i) % $this->capacity;
            if ($index < 0) {
                $index += $this->capacity;
            }
            $result[] = $this->buffer[$index];
        }
        return $result;
    }

    public function clear(): void
    {
        $this->buffer = [];
        $this->head = 0;
        $this->size = 0;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }
}

/**
 * FusionEngine performs multi-source, multi-dimensional information fusion.
 *
 * Each source has:
 * - a unique identifier
 * - per-dimension weight (positive float)
 * - a circular buffer of recent vectors (resource constrained)
 *
 * Fusion result is a weighted average per dimension across all sources,
 * using the mean of each source's buffer.
 */
final class FusionEngine
{
    /** @var array<string, array<string, float>> */
    private array $sourceWeights = [];

    /** @var array<string, CircularBuffer> */
    private array $sourceBuffers = [];

    private int $bufferCapacity;

    /**
     * @param int $bufferCapacity Number of recent samples kept per source.
     */
    public function __construct(int $bufferCapacity = 5)
    {
        if ($bufferCapacity <= 0) {
            throw new InvalidArgumentException('Buffer capacity must be positive');
        }
        $this->bufferCapacity = $bufferCapacity;
    }

    /**
     * Register a new source with per-dimension weights.
     *
     * @param string $sourceId
     * @param array<string, float> $dimensionWeights Positive weights per dimension.
     */
    public function addSource(string $sourceId, array $dimensionWeights): void
    {
        if (isset($this->sourceWeights[$sourceId])) {
            throw new InvalidArgumentException("Source '{$sourceId}' already exists");
        }
        foreach ($dimensionWeights as $dim => $w) {
            if (!is_string($dim) || $dim === '' || $w <= 0) {
                throw new InvalidArgumentException('Invalid dimension weight specification');
            }
        }
        $this->sourceWeights[$sourceId] = $dimensionWeights;
        $this->sourceBuffers[$sourceId] = new CircularBuffer($this->bufferCapacity);
    }

    /**
     * Ingest a new measurement vector from a source.
     *
     * @param string $sourceId
     * @param array<string, float> $vector
     */
    public function ingest(string $sourceId, array $vector): void
    {
        if (!isset($this->sourceBuffers[$sourceId])) {
            throw new InvalidArgumentException("Unknown source '{$sourceId}'");
        }
        // Validate vector dimensions match declared weights (allow subset)
        foreach ($vector as $dim => $value) {
            if (!array_key_exists($dim, $this->sourceWeights[$sourceId])) {
                throw new InvalidArgumentException("Dimension '{$dim}' not declared for source '{$sourceId}'");
            }
            if (!is_float($value) && !is_int($value)) {
                throw new InvalidArgumentException('Vector values must be numeric');
            }
        }
        // Cast to float
        $floatVector = [];
        foreach ($vector as $dim => $value) {
            $floatVector[$dim] = (float)$value;
        }
        $this->sourceBuffers[$sourceId]->push($floatVector);
    }

    /**
     * Compute the fused vector.
     *
     * @return array<string, float> Weighted average per dimension.
     */
    public function getFusion(): array
    {
        if (empty($this->sourceWeights)) {
            return [];
        }

        // Aggregate per-dimension sums and total weights
        $dimSums = [];      // dimension => sum of (sourceMean * weight)
        $dimWeightSum = []; // dimension => sum of weights

        foreach ($this->sourceWeights as $sourceId => $weights) {
            $buffer = $this->sourceBuffers[$sourceId];
            $samples = $buffer->getAll();
            if (empty($samples)) {
                continue; // No data from this source yet
            }

            // Compute per-dimension mean for this source
            $sourceMean = [];
            $count = count($samples);
            foreach ($samples as $sample) {
                foreach ($sample as $dim => $value) {
                    $sourceMean[$dim] = ($sourceMean[$dim] ?? 0.0) + $value / $count;
                }
            }

            // Apply per-dimension weights
            foreach ($weights as $dim => $weight) {
                if (!isset($sourceMean[$dim])) {
                    continue; // source has no recent value for this dimension
                }
                $dimSums[$dim] = ($dimSums[$dim] ?? 0.0) + $sourceMean[$dim] * $weight;
                $dimWeightSum[$dim] = ($dimWeightSum[$dim] ?? 0.0) + $weight;
            }
        }

        // Final fused vector
        $fused = [];
        foreach ($dimSums as $dim => $sum) {
            $totalWeight = $dimWeightSum[$dim];
            if ($totalWeight > 0) {
                $fused[$dim] = $sum / $totalWeight;
            }
        }
        ksort($fused);
        return $fused;
    }

    /**
     * Reset all buffers and source registrations.
     */
    public function reset(): void
    {
        foreach ($this->sourceBuffers as $buffer) {
            $buffer->clear();
        }
        $this->sourceWeights = [];
        $this->sourceBuffers = [];
    }

    /**
     * Get internal buffer size for a source (useful for tests).
     */
    public function getBufferSize(string $sourceId): int
    {
        if (!isset($this->sourceBuffers[$sourceId])) {
            throw new InvalidArgumentException("Unknown source '{$sourceId}'");
        }
        return $this->sourceBuffers[$sourceId]->getSize();
    }
}
