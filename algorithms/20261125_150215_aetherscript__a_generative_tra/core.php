<?php
declare(strict_types=1);

/**
 * Simple representation of a handwriting stroke.
 */
final class Stroke
{
    public float $x;
    public float $y;
    public float $pressure;

    public function __construct(float $x, float $y, float $pressure)
    {
        $this->x = $x;
        $this->y = $y;
        $this->pressure = $pressure;
    }

    /** @return array{float,float,float} */
    public function toArray(): array
    {
        return [$this->x, $this->y, $this->pressure];
    }

    /**
     * @param array{float,float,float} $arr
     * @return self
     */
    public static function fromArray(array $arr): self
    {
        return new self($arr[0], $arr[1], $arr[2]);
    }
}

/**
 * Immutable sequence of strokes.
 */
final class Sequence
{
    /** @var Stroke[] */
    private array $strokes;

    /**
     * @param Stroke[] $strokes
     */
    public function __construct(array $strokes = [])
    {
        $this->strokes = $strokes;
    }

    /** @return Stroke[] */
    public function getStrokes(): array
    {
        return $this->strokes;
    }

    public function length(): int
    {
        return count($this->strokes);
    }

    public function append(Stroke $stroke): self
    {
        $new = $this->strokes;
        $new[] = $stroke;
        return new self($new);
    }

    /** @return array<array{float,float,float}> */
    public function toArray(): array
    {
        $out = [];
        foreach ($this->strokes as $s) {
            $out[] = $s->toArray();
        }
        return $out;
    }

    /**
     * @param array<array{float,float,float}> $arr
     * @return self
     */
    public static function fromArray(array $arr): self
    {
        $strokes = [];
        foreach ($arr as $vec) {
            $strokes[] = Stroke::fromArray($vec);
        }
        return new self($strokes);
    }
}

/**
 * A minimal deterministic transformer-like generator for stroke sequences.
 * It uses pure linear algebra (embedding, scaled dot‑product attention, linear output).
 */
final class SimpleTransformer
{
    private int $embedSize;
    private int $inputSize = 3;   // x, y, pressure
    private int $outputSize = 3;  // same as input

    /** @var float[][] */
    private array $embeddingMatrix;   // embedSize x inputSize
    /** @var float[][] */
    private array $outputMatrix;      // outputSize x embedSize

    /**
     * @param int $embedSize Dimensionality of internal representation.
     * @param int $seed Deterministic seed for weight initialization.
     */
    public function __construct(int $embedSize = 4, int $seed = 42)
    {
        $this->embedSize = $embedSize;
        mt_srand($seed);
        $this->embeddingMatrix = $this->randomMatrix($embedSize, $this->inputSize);
        $this->outputMatrix    = $this->randomMatrix($this->outputSize, $embedSize);
    }

    /**
     * Generate a new sequence extending $seed by $steps strokes.
     *
     * @param Sequence $seed
     * @param int $steps Number of strokes to generate.
     * @return Sequence
     */
    public function generate(Sequence $seed, int $steps): Sequence
    {
        $generated = $seed;
        for ($i = 0; $i < $steps; $i++) {
            $next = $this->step($generated);
            $generated = $generated->append($next);
        }
        return $generated;
    }

    /**
     * Perform a single generation step using the last stroke as query.
     *
     * @param Sequence $seq
     * @return Stroke
     */
    private function step(Sequence $seq): Stroke
    {
        $embeds = $this->encode($seq);
        $contexts = $this->attention($embeds);
        $lastContext = $contexts[count($contexts) - 1];
        $outVec = $this->linear($lastContext, $this->outputMatrix);
        // Clamp values to reasonable range [0,1] for demonstration.
        $outVec = array_map(fn($v) => max(0.0, min(1.0, $v)), $outVec);
        return Stroke::fromArray($outVec);
    }

    /**
     * Encode a sequence into embedding vectors.
     *
     * @param Sequence $seq
     * @return float[][] Each row is an embedding vector.
     */
    public function encode(Sequence $seq): array
    {
        $vectors = [];
        foreach ($seq->getStrokes() as $stroke) {
            $vec = $stroke->toArray(); // length 3
            $vectors[] = $this->linear($vec, $this->embeddingMatrix);
        }
        return $vectors; // shape: len x embedSize
    }

    /**
     * Scaled dot‑product attention (self‑attention).
     *
     * @param float[][] $embeds Shape: N x embedSize
     * @return float[][] Same shape, each row is a context vector.
     */
    public function attention(array $embeds): array
    {
        $n = count($embeds);
        if ($n === 0) {
            return [];
        }
        $contexts = [];
        $scale = 1.0 / sqrt((float)$this->embedSize);
        for ($i = 0; $i < $n; $i++) {
            $scores = [];
            for ($j = 0; $j < $n; $j++) {
                $dot = $this->dot($embeds[$i], $embeds[$j]);
                $scores[] = $dot * $scale;
            }
            $weights = $this->softmax($scores);
            $ctx = $this->weightedSum($embeds, $weights);
            $contexts[] = $ctx;
        }
        return $contexts;
    }

    /** @return float[] */
    private function linear(array $vec, array $matrix): array
    {
        // matrix dimensions: rows x cols, vec length = cols
        $result = [];
        foreach ($matrix as $row) {
            $result[] = $this->dot($row, $vec);
        }
        return $result;
    }

    /** @return float */
    private function dot(array $a, array $b): float
    {
        $sum = 0.0;
        $len = count($a);
        for ($i = 0; $i < $len; $i++) {
            $sum += $a[$i] * $b[$i];
        }
        return $sum;
    }

    /**
     * @param float[] $weights Length N, sum to 1.
     * @param float[][] $vectors Length N, each vector same dimension.
     * @return float[] Weighted sum vector.
     */
    private function weightedSum(array $vectors, array $weights): array
    {
        $dim = count($vectors[0]);
        $out = array_fill(0, $dim, 0.0);
        $n = count($vectors);
        for ($i = 0; $i < $n; $i++) {
            for ($d = 0; $d < $dim; $d++) {
                $out[$d] += $weights[$i] * $vectors[$i][$d];
            }
        }
        return $out;
    }

    /** @return float[] */
    private function softmax(array $logits): array
    {
        $max = max($logits);
        $exp = [];
        $sum = 0.0;
        foreach ($logits as $v) {
            $e = exp($v - $max);
            $exp[] = $e;
            $sum += $e;
        }
        $out = [];
        foreach ($exp as $e) {
            $out[] = $e / $sum;
        }
        return $out;
    }

    /**
     * Create a matrix with values in [-0.5,0.5].
     *
     * @param int $rows
     * @param int $cols
     * @return float[][]
     */
    private function randomMatrix(int $rows, int $cols): array
    {
        $mat = [];
        for ($i = 0; $i < $rows; $i++) {
            $row = [];
            for ($j = 0; $j < $cols; $j++) {
                $row[] = (mt_rand() / mt_getrandmax()) - 0.5;
            }
            $mat[] = $row;
        }
        return $mat;
    }
}
