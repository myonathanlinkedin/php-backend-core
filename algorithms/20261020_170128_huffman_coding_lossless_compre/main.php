<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

/**
 * Simple assertion wrapper to provide clearer messages.
 */
function expect_equal(mixed $actual, mixed $expected, string $msg = ''): void
{
    assert($actual === $expected, $msg ?: "Expected " . var_export($expected, true) . " but got " . var_export($actual, true));
}

/**
 * Unit tests for HuffmanCoder.
 */
function test_empty_string(): void
{
    $data = '';
    $enc = HuffmanCoder::compress($data);
    expect_equal($enc['bits'], '');
    expect_equal($enc['tree'], '');
    $dec = HuffmanCoder::decompress($enc['bits'], $enc['tree']);
    expect_equal($dec, $data, 'Empty string round‑trip failed');
}

function test_single_character(): void
{
    $data = str_repeat('a', 100);
    $enc = HuffmanCoder::compress($data);
    // With only one symbol the code should be '0' repeated.
    expect_equal($enc['bits'], str_repeat('0', 100));
    $dec = HuffmanCoder::decompress($enc['bits'], $enc['tree']);
    expect_equal($dec, $data);
}

function test_multiple_characters(): void
{
    $data = "this is an example for huffman encoding";
    $enc = HuffmanCoder::compress($data);
    $dec = HuffmanCoder::decompress($enc['bits'], $enc['tree']);
    expect_equal($dec, $data);
}

function test_all_ascii(): void
{
    $data = '';
    for ($i = 0; $i < 128; ++$i) {
        $data .= chr($i);
    }
    $enc = HuffmanCoder::compress($data);
    $dec = HuffmanCoder::decompress($enc['bits'], $enc['tree']);
    expect_equal($dec, $data);
}

function test_unicode_bytes(): void
{
    // UTF‑8 multibyte characters are treated as raw bytes.
    $data = "😀😃😄😁😆"; // each emoji is 4 bytes.
    $enc = HuffmanCoder::compress($data);
    $dec = HuffmanCoder::decompress($enc['bits'], $enc['tree']);
    expect_equal($dec, $data);
}

/**
 * Verify prefix‑free property of generated codes.
 */
function test_prefix_free(): void
{
    $data = "abracadabra";
    $root = HuffmanTree::build($data);
    $codes = [];
    HuffmanTree::generateCodes($root, '', $codes);
    $codeList = array_values($codes);
    $n = count($codeList);
    for ($i = 0; $i < $n; ++$i) {
        for ($j = 0; $j < $n; ++$j) {
            if ($i === $j) continue;
            $a = $codeList[$i];
            $b = $codeList[$j];
            assert(str_starts_with($b, $a) === false, "Code $a is a prefix of $b");
        }
    }
}

/**
 * Run all tests.
 */
function run_tests(): void
{
    test_empty_string();
    test_single_character();
    test_multiple_characters();
    test_all_ascii();
    test_unicode_bytes();
    test_prefix_free();
    echo "All Huffman tests passed.\n";
}

run_tests();
