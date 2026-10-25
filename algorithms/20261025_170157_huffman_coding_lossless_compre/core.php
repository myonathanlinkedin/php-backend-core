<?php

class HuffmanNode
{
    public ?string $char;
    public int $freq;
    public ?HuffmanNode $left;
    public ?HuffmanNode $right;

    public function __construct(?string $char, int $freq, ?HuffmanNode $left = null, ?HuffmanNode $right = null)
    {
        $this->char = $char;
        $this->freq = $freq;
        $this->left = $left;
        $this->right = $right;
    }
}

class Huffman
{
    public static function buildTree(array $freq): HuffmanNode
    {
        $queue = new SplPriorityQueue();
        $queue->setExtractFlags(SplPriorityQueue::EXTR_DATA);

        foreach ($freq as $char => $f) {
            $node = new HuffmanNode($char, $f);
            $queue->insert($node, -$f);
        }

        if ($queue->count() === 0) {
            throw new InvalidArgumentException('Frequency map is empty.');
        }

        while ($queue->count() > 1) {
            $node1 = $queue->extract();
            $node2 = $queue->extract();
            $merged = new HuffmanNode(null, $node1->freq + $node2->freq, $node1, $node2);
            $queue->insert($merged, -$merged->freq);
        }

        return $queue->extract();
    }

    public static function generateCodes(HuffmanNode $root): array
    {
        $codes = [];
        self::traverse($root, '', $codes);
        return $codes;
    }

    private static function traverse(HuffmanNode $node, string $prefix, array &$codes): void
    {
        if ($node->char !== null) {
            $codes[$node->char] = $prefix === '' ? '0' : $prefix;
            return;
        }
        if ($node->left !== null) {
            self::traverse($node->left, $prefix . '0', $codes);
        }
        if ($node->right !== null) {
            self::traverse($node->right, $prefix . '1', $codes);
        }
    }

    public static function encode(string $data, array $codes): string
    {
        $bits = '';
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $ch = $data[$i];
            if (!isset($codes[$ch])) {
                throw new InvalidArgumentException("Character '{$ch}' not in code table.");
            }
            $bits .= $codes[$ch];
        }
        return $bits;
    }

    public static function decode(string $bits, HuffmanNode $root): string
    {
        $output = '';
        $node = $root;
        $len = strlen($bits);
        for ($i = 0; $i < $len; $i++) {
            $bit = $bits[$i];
            if ($bit === '0') {
                $node = $node->left;
            } else {
                $node = $node->right;
            }
            if ($node === null) {
                throw new RuntimeException('Invalid bit sequence.');
            }
            if ($node->char !== null) {
                $output .= $node->char;
                $node = $root;
            }
        }
        if ($node !== $root) {
            throw new RuntimeException('Incomplete bit sequence.');
        }
        return $output;
    }
}
