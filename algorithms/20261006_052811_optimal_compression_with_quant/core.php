<?php
declare(strict_types=1);

final class HuffmanNode
{
    public ?int $freq;
    public ?string $char;
    public ?self $left;
    public ?self $right;

    public function __construct(?int $freq = null, ?string $char = null, ?self $left = null, ?self $right = null)
    {
        $this->freq = $freq;
        $this->char = $char;
        $this->left = $left;
        $this->right = $right;
    }

    public function isLeaf(): bool
    {
        return $this->left === null && $this->right === null;
    }
}

final class HuffmanTree
{
    private HuffmanNode $root;
    /** @var array<string, string> */
    private array $codes = [];

    /**
     * @param array<string, int> $freqs
     */
    public function __construct(array $freqs)
    {
        $this->root = $this->buildTree($freqs);
        $this->generateCodes($this->root, '');
    }

    private function buildTree(array $freqs): HuffmanNode
    {
        $queue = new SplPriorityQueue();
        $queue->setExtractFlags(SplPriorityQueue::EXTR_DATA);
        foreach ($freqs as $char => $freq) {
            $node = new HuffmanNode($freq, $char);
            // SplPriorityQueue extracts highest priority, so use negative freq
            $queue->insert($node, -$freq);
        }

        while ($queue->count() > 1) {
            $left = $queue->extract();
            $right = $queue->extract();
            $mergedFreq = ($left->freq ?? 0) + ($right->freq ?? 0);
            $parent = new HuffmanNode($mergedFreq, null, $left, $right);
            $queue->insert($parent, -$mergedFreq);
        }

        return $queue->extract();
    }

    private function generateCodes(HuffmanNode $node, string $prefix): void
    {
        if ($node->isLeaf() && $node->char !== null) {
            $this->codes[$node->char] = $prefix === '' ? '0' : $prefix;
            return;
        }
        if ($node->left !== null) {
            $this->generateCodes($node->left, $prefix . '0');
        }
        if ($node->right !== null) {
            $this->generateCodes($node->right, $prefix . '1');
        }
    }

    /**
     * @return array<string, string>
     */
    public function getCodes(): array
    {
        return $this->codes;
    }

    public function getRoot(): HuffmanNode
    {
        return $this->root;
    }
}

final class Compressor
{
    private HuffmanTree $tree;

    public function __construct(string $sample)
    {
        $freqs = $this->frequencyTable($sample);
        $this->tree = new HuffmanTree($freqs);
    }

    /**
     * @return array<string, int>
     */
    private function frequencyTable(string $data): array
    {
        $freqs = [];
        $len = strlen($data);
        for ($i = 0; $i < $len; ++$i) {
            $ch = $data[$i];
            $freqs[$ch] = ($freqs[$ch] ?? 0) + 1;
        }
        return $freqs;
    }

    /**
     * @return int[] Bit array (0/1)
     */
    public function compress(string $data): array
    {
        $codes = $this->tree->getCodes();
        $bits = [];
        $len = strlen($data);
        for ($i = 0; $i < $len; ++$i) {
            $ch = $data[$i];
            $code = $codes[$ch] ?? '';
            foreach (str_split($code) as $b) {
                $bits[] = (int)$b;
            }
        }
        return $bits;
    }

    public function decompress(array $bits): string
    {
        $root = $this->tree->getRoot();
        $result = '';
        $node = $root;
        foreach ($bits as $bit) {
            $node = ($bit === 0) ? $node->left : $node->right;
            if ($node === null) {
                throw new RuntimeException('Corrupted bitstream');
            }
            if ($node->isLeaf()) {
                $result .= $node->char;
                $node = $root;
            }
        }
        return $result;
    }

    /**
     * Simulated quantum retrieval: O(log N) access to the character at position $pos (0‑based)
     *
     * @param int[] $bits
     */
    public function getCharAt(array $bits, int $pos): string
    {
        $root = $this->tree->getRoot();
        $node = $root;
        $index = 0;
        foreach ($bits as $bit) {
            $node = ($bit === 0) ? $node->left : $node->right;
            if ($node === null) {
                throw new RuntimeException('Corrupted bitstream');
            }
            if ($node->isLeaf()) {
                if ($index === $pos) {
                    return $node->char;
                }
                ++$index;
                $node = $root;
            }
        }
        throw new OutOfBoundsException('Position out of range');
    }

    public function getTree(): HuffmanTree
    {
        return $this->tree;
    }
}
