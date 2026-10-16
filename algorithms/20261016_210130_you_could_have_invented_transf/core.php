<?php
declare(strict_types=1);

/**
 * Simple linear algebra utilities for dense matrices represented as
 * array of arrays (row-major). All operations are pure and deterministic.
 */
final class LinearAlgebra
{
    /** @return array<int, array<int, float>> */
    public static function matMul(array $a, array $b): array
    {
        $aRows = count($a);
        $aCols = count($a[0] ?? []);
        $bRows = count($b);
        $bCols = count($b[0] ?? []);
        if ($aCols !== $bRows) {
            throw new InvalidArgumentException('Incompatible dimensions for matrix multiplication.');
        }
        $result = array_fill(0, $aRows, array_fill(0, $bCols, 0.0));
        for ($i = 0; $i < $aRows; $i++) {
            for ($k = 0; $k < $aCols; $k++) {
                $aik = $a[$i][$k];
                for ($j = 0; $j < $bCols; $j++) {
                    $result[$i][$j] += $aik * $b[$k][$j];
                }
            }
        }
        return $result;
    }

    /** @return array<int, array<int, float>> */
    public static function transpose(array $m): array
    {
        $rows = count($m);
        $cols = count($m[0] ?? []);
        $t = array_fill(0, $cols, array_fill(0, $rows, 0.0));
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $t[$j][$i] = $m[$i][$j];
            }
        }
        return $t;
    }

    /** @return array<int, float> */
    public static function softmax(array $logits): array
    {
        $max = max($logits);
        $exp = [];
        $sum = 0.0;
        foreach ($logits as $v) {
            $e = exp($v - $max);
            $exp[] = $e;
            $sum += $e;
        }
        if ($sum == 0.0) {
            throw new RuntimeException('Softmax sum is zero.');
        }
        foreach ($exp as &$e) {
            $e /= $sum;
        }
        return $exp;
    }

    /** @return array<int, array<int, float>> */
    public static function softmaxRows(array $matrix): array
    {
        $result = [];
        foreach ($matrix as $row) {
            $result[] = self::softmax($row);
        }
        return $result;
    }
}

/**
 * Linear layer with weight matrix and optional bias.
 */
final class Linear
{
    /** @var array<int, array<int, float>> */
    private array $weight;
    /** @var array<int, float> */
    private array $bias;

    /**
     * @param array<int, array<int, float>> $weight shape (in_features, out_features)
     * @param array<int, float> $bias shape (out_features)
     */
    public function __construct(array $weight, array $bias = [])
    {
        $this->weight = $weight;
        $this->bias = $bias;
    }

    /** @return array<int, array<int, float>> */
    public function forward(array $input): array
    {
        // input: (seq_len, in_features)
        $output = LinearAlgebra::matMul($input, $this->weight);
        if (!empty($this->bias)) {
            foreach ($output as &$row) {
                foreach ($row as $j => &$val) {
                    $val += $this->bias[$j];
                }
                unset($val);
            }
            unset($row);
        }
        return $output;
    }
}

/**
 * Scaled dot‑product multi‑head self‑attention (single‑head variant for clarity).
 */
final class MultiHeadAttention
{
    private Linear $linearQ;
    private Linear $linearK;
    private Linear $linearV;
    private Linear $linearOut;
    private int $headDim;
    private float $scale;

    /**
     * @param Linear $linearQ
     * @param Linear $linearK
     * @param Linear $linearV
     * @param Linear $linearOut
     * @param int $headDim dimension of each head (d_k)
     */
    public function __construct(Linear $linearQ, Linear $linearK, Linear $linearV, Linear $linearOut, int $headDim)
    {
        $this->linearQ = $linearQ;
        $this->linearK = $linearK;
        $this->linearV = $linearV;
        $this->linearOut = $linearOut;
        $this->headDim = $headDim;
        $this->scale = 1.0 / sqrt((float)$headDim);
    }

    /**
     * @param array<int, array<int, float>> $x (seq_len, d_model)
     * @return array<int, array<int, float>> (seq_len, d_model)
     */
    public function forward(array $x): array
    {
        $q = $this->linearQ->forward($x); // (seq_len, d_model)
        $k = $this->linearK->forward($x);
        $v = $this->linearV->forward($x);

        // scores = Q * K^T * scale
        $kt = LinearAlgebra::transpose($k);
        $scores = LinearAlgebra::matMul($q, $kt);
        foreach ($scores as &$row) {
            foreach ($row as &$val) {
                $val *= $this->scale;
            }
            unset($val);
        }
        unset($row);

        // softmax over each row
        $attn = LinearAlgebra::softmaxRows($scores);

        // weighted sum
        $context = LinearAlgebra::matMul($attn, $v);

        // final linear projection
        return $this->linearOut->forward($context);
    }
}

/**
 * Position‑wise feed‑forward network (two linear layers with ReLU).
 */
final class FeedForward
{
    private Linear $linear1;
    private Linear $linear2;

    public function __construct(Linear $linear1, Linear $linear2)
    {
        $this->linear1 = $linear1;
        $this->linear2 = $linear2;
    }

    /** @return array<int, array<int, float>> */
    public function forward(array $x): array
    {
        $h = $this->linear1->forward($x);
        // ReLU
        foreach ($h as &$row) {
            foreach ($row as &$val) {
                $val = max(0.0, $val);
            }
            unset($val);
        }
        unset($row);
        return $this->linear2->forward($h);
    }
}

/**
 * Minimal Transformer encoder block (attention + feed‑forward) with residual connections.
 */
final class TransformerEncoder
{
    private MultiHeadAttention $selfAttn;
    private FeedForward $ffn;

    public function __construct(MultiHeadAttention $selfAttn, FeedForward $ffn)
    {
        $this->selfAttn = $selfAttn;
        $this->ffn = $ffn;
    }

    /**
     * @param array<int, array<int, float>> $x (seq_len, d_model)
     * @return array<int, array<int, float>>
     */
    public function forward(array $x): array
    {
        // Self‑attention + residual
        $attnOut = $this->selfAttn->forward($x);
        $x = self::add($x, $attnOut);

        // Feed‑forward + residual
        $ffnOut = $this->ffn->forward($x);
        $x = self::add($x, $ffnOut);

        return $x;
    }

    /** @return array<int, array<int, float>> */
    private static function add(array $a, array $b): array
    {
        $result = [];
        foreach ($a as $i => $rowA) {
            $rowB = $b[$i];
            $row = [];
            foreach ($rowA as $j => $valA) {
                $row[] = $valA + $rowB[$j];
            }
            $result[] = $row;
        }
        return $result;
    }
}
