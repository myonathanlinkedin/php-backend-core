<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

/**
 * Helper to compare two matrices with tolerance.
 */
function assertMatrixEquals(array $a, array $b, float $eps = 1e-5): void
{
    $rows = count($a);
    $cols = count($a[0] ?? []);
    assert($rows === count($b), 'Row count mismatch');
    assert($cols === count($b[0] ?? []), 'Column count mismatch');
    for ($i = 0; $i < $rows; $i++) {
        for ($j = 0; $j < $cols; $j++) {
            $diff = abs($a[$i][$j] - $b[$i][$j]);
            assert($diff < $eps, "Mismatch at ($i,$j): {$a[$i][$j]} vs {$b[$i][$j]}");
        }
    }
}

/**
 * Build an identity linear layer (weight = I, bias = 0).
 *
 * @param int $dim
 * @return Linear
 */
function identityLinear(int $dim): Linear
{
    $weight = [];
    for ($i = 0; $i < $dim; $i++) {
        $row = array_fill(0, $dim, 0.0);
        $row[$i] = 1.0;
        $weight[] = $row;
    }
    $bias = array_fill(0, $dim, 0.0);
    return new Linear($weight, $bias);
}

/**
 * Unit test for single‑head attention with identity projections.
 */
function testAttentionIdentity(): void
{
    $dModel = 4; // d_k = d_v = d_model for identity case
    $headDim = $dModel;

    // Identity linear layers for Q, K, V, and output projection
    $linearQ = identityLinear($dModel);
    $linearK = identityLinear($dModel);
    $linearV = identityLinear($dModel);
    $linearOut = identityLinear($dModel);

    $selfAttn = new MultiHeadAttention($linearQ, $linearK, $linearV, $linearOut, $headDim);

    // Input sequence: two tokens, one‑hot like vectors
    $x = [
        [1.0, 0.0, 0.0, 0.0],
        [0.0, 1.0, 0.0, 0.0],
    ];

    $out = $selfAttn->forward($x);

    // Expected output computed analytically (see analysis)
    $expected = [
        [0.6224593312018546, 0.3775406687981454, 0.0, 0.0],
        [0.3775406687981454, 0.6224593312018546, 0.0, 0.0],
    ];

    assertMatrixEquals($out, $expected);
}

/**
 * Unit test for full encoder block with identity weights.
 * Since all linear layers are identity and ReLU is a no‑op on positive values,
 * the encoder reduces to two residual additions, i.e. output = input * 3.
 */
function testEncoderIdentity(): void
{
    $dModel = 4;
    $headDim = $dModel;

    $linearQ = identityLinear($dModel);
    $linearK = identityLinear($dModel);
    $linearV = identityLinear($dModel);
    $linearOut = identityLinear($dModel);
    $selfAttn = new MultiHeadAttention($linearQ, $linearK, $linearV, $linearOut, $headDim);

    // Feed‑forward: two identity layers (ReLU passes through)
    $ffLinear1 = identityLinear($dModel);
    $ffLinear2 = identityLinear($dModel);
    $ffn = new FeedForward($ffLinear1, $ffLinear2);

    $encoder = new TransformerEncoder($selfAttn, $ffn);

    $x = [
        [1.0, 2.0, -1.0, 0.5],
        [0.0, -3.0, 4.0, 1.0],
    ];

    $out = $encoder->forward($x);

    // Expected: input + attention_out + feedforward_out
    // attention_out == input (identity) scaled by softmax as in testAttentionIdentity,
    // but because weights are identity, the attention output is the same as in testAttentionIdentity.
    // For simplicity we compute expected using the same functions (deterministic) and compare.
    $attn = $selfAttn->forward($x);
    $res1 = [];
    foreach ($x as $i => $row) {
        $res1[] = array_map(fn($a, $b) => $a + $b, $row, $attn[$i]);
    }
    $ff = $ffn->forward($res1);
    $expected = [];
    foreach ($res1 as $i => $row) {
        $expected[] = array_map(fn($a, $b) => $a + $b, $row, $ff[$i]);
    }

    assertMatrixEquals($out, $expected);
}

// Execute tests
testAttentionIdentity();
testEncoderIdentity();

echo "All tests passed.\n";
