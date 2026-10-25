<?php
declare(strict_types=1);

require_once 'core.php';

function frequencyMap(string $data): array
{
    $freq = [];
    $len = strlen($data);
    for ($i = 0; $i < $len; $i++) {
        $ch = $data[$i];
        if (!isset($freq[$ch])) {
            $freq[$ch] = 0;
        }
        $freq[$ch]++;
    }
    return $freq;
}

function testEmptyString(): void
{
    $data = '';
    $freq = frequencyMap($data);
    assert($freq === []);
    $encoded = '';
    $decoded = '';
    assert($encoded === '');
    assert($decoded === '');
}

function testSingleChar(): void
{
    $data = 'aaaaaa';
    $freq = frequencyMap($data);
    $root = Huffman::buildTree($freq);
    $codes = Huffman::generateCodes($root);
    assert(isset($codes['a']));
    $encoded = Huffman::encode($data, $codes);
    $decoded = Huffman::decode($encoded, $root);
    assert($decoded === $data);
}

function testAllUnique(): void
{
    $data = 'abcdef';
    $freq = frequencyMap($data);
    $root = Huffman::buildTree($freq);
    $codes = Huffman::generateCodes($root);
    $encoded = Huffman::encode($data, $codes);
    $decoded = Huffman::decode($encoded, $root);
    assert($decoded === $data);
}

function testMixed(): void
{
    $data = 'this is an example of a huffman tree';
    $freq = frequencyMap($data);
    $root = Huffman::buildTree($freq);
    $codes = Huffman::generateCodes($root);
    $encoded = Huffman::encode($data, $codes);
    $decoded = Huffman::decode($encoded, $root);
    assert($decoded === $data);
}

function testLargeString(): void
{
    $data = str_repeat('abcde', 1000);
    $freq = frequencyMap($data);
    $root = Huffman::buildTree($freq);
    $codes = Huffman::generateCodes($root);
    $encoded = Huffman::encode($data, $codes);
    $decoded = Huffman::decode($encoded, $root);
    assert($decoded === $data);
}

testEmptyString();
testSingleChar();
testAllUnique();
testMixed();
testLargeString();

echo "All tests passed.\n";
