<?php
declare(strict_types=1);

/**
 * Huffman coding core implementation.
 */
final class HuffmanNode
{
    public ?HuffmanNode $left;
    public ?HuffmanNode $right;
    public ?string $char;
    public int $freq;

    public function __construct(int $freq, ?string $char = null, ?HuffmanNode $left = null, ?HuffmanNode $right = null)
    {
        $this->freq  = $freq;
        $this->char  = $char;
        $this->left  = $left;
        $this->right = $right;
    }

    public function isLeaf(): bool
    {
        return $this->char !== null;
    }
}

/**
 * Minimal binary min‑heap for HuffmanNode objects.
 */
final class MinHeap
{
    /** @var HuffmanNode[] */
    private array $data = [];

    public function insert(HuffmanNode $node): void
    {
        $this->data[] = $node;
        $this->siftUp(count($this->data) - 1);
    }

    public function extractMin(): HuffmanNode
    {
        $min = $this->data[0];
        $last = array_pop($this->data);
        if (!empty($this->data)) {
            $this->data[0] = $last;
            $this->siftDown(0);
        }
        return $min;
    }

    public function count(): int
    {
        return count($this->data);
    }

    private function siftUp(int $idx): void
    {
        while ($idx > 0) {
            $parent = intdiv($idx - 1, 2);
            if ($this->data[$parent]->freq <= $this->data[$idx]->freq) {
                break;
            }
            $this->swap($parent, $idx);
            $idx = $parent;
        }
    }

    private function siftDown(int $idx): void
    {
        $n = count($this->data);
        while (true) {
            $left = $idx * 2 + 1;
            $right = $left + 1;
            $smallest = $idx;

            if ($left < $n && $this->data[$left]->freq < $this->data[$smallest]->freq) {
                $smallest = $left;
            }
            if ($right < $n && $this->data[$right]->freq < $this->data[$smallest]->freq) {
                $smallest = $right;
            }
            if ($smallest === $idx) {
                break;
            }
            $this->swap($idx, $smallest);
            $idx = $smallest;
        }
    }

    private function swap(int $i, int $j): void
    {
        $tmp = $this->data[$i];
        $this->data[$i] = $this->data[$j];
        $this->data[$j] = $tmp;
    }
}

/**
 * Huffman tree utilities.
 */
final class HuffmanTree
{
    /**
     * Build a Huffman tree from raw data.
     */
    public static function build(string $data): ?HuffmanNode
    {
        if ($data === '') {
            return null;
        }

        $freq = [];
        $len = strlen($data);
        for ($i = 0; $i < $len; ++$i) {
            $c = $data[$i];
            $freq[$c] = ($freq[$c] ?? 0) + 1;
        }

        $heap = new MinHeap();
        foreach ($freq as $c => $f) {
            $heap->insert(new HuffmanNode($f, $c));
        }

        while ($heap->count() > 1) {
            $a = $heap->extractMin();
            $b = $heap->extractMin();
            $merged = new HuffmanNode($a->freq + $b->freq, null, $a, $b);
            $heap->insert($merged);
        }

        return $heap->extractMin();
    }

    /**
     * Serialize tree using pre‑order traversal.
     * Internal node: byte '0'
     * Leaf node: byte '1' followed by the character byte.
     */
    public static function serialize(?HuffmanNode $node): string
    {
        if ($node === null) {
            return '';
        }
        if ($node->isLeaf()) {
            return '1' . $node->char;
        }
        return '0' . self::serialize($node->left) . self::serialize($node->right);
    }

    /**
     * Reconstruct tree from serialized representation.
     */
    public static function deserialize(string $s): ?HuffmanNode
    {
        $pos = 0;
        $len = strlen($s);
        $read = static function () use (&$pos, $len, $s): string {
            if ($pos >= $len) {
                throw new RuntimeException('Invalid serialized Huffman tree.');
            }
            return $s[$pos++];
        };

        $build = static function () use (&$read, &$build): HuffmanNode {
            $marker = $read();
            if ($marker === '1') {
                $char = $read();
                return new HuffmanNode(0, $char);
            }
            // marker == '0'
            $left = $build();
            $right = $build();
            return new HuffmanNode(0, null, $left, $right);
        };

        if ($s === '') {
            return null;
        }
        return $build();
    }

    /**
     * Generate prefix codes for each character.
     */
    public static function generateCodes(?HuffmanNode $node, string $prefix, array &$map): void
    {
        if ($node === null) {
            return;
        }
        if ($node->isLeaf()) {
            $map[$node->char] = $prefix === '' ? '0' : $prefix; // handle single‑symbol case
            return;
        }
        self::generateCodes($node->left, $prefix . '0', $map);
        self::generateCodes($node->right, $prefix . '1', $map);
    }
}

/**
 * Public API for compression / decompression.
 */
final class HuffmanCoder
{
    /**
     * Compress a string.
     *
     * @return array{bits:string, tree:string}
     */
    public static function compress(string $data): array
    {
        $root = HuffmanTree::build($data);
        if ($root === null) {
            return ['bits' => '', 'tree' => ''];
        }

        $codes = [];
        HuffmanTree::generateCodes($root, '', $codes);

        $bits = '';
        $len = strlen($data);
        for ($i = 0; $i < $len; ++$i) {
            $bits .= $codes[$data[$i]];
        }

        $tree = HuffmanTree::serialize($root);
        return ['bits' => $bits, 'tree' => $tree];
    }

    /**
     * Decompress using bit string and serialized tree.
     */
    public static function decompress(string $bits, string $tree): string
    {
        if ($bits === '' && $tree === '') {
            return '';
        }

        $root = HuffmanTree::deserialize($tree);
        if ($root === null) {
            throw new RuntimeException('Cannot decompress without a tree.');
        }

        $result = '';
        $node = $root;
        $len = strlen($bits);
        for ($i = 0; $i < $len; ++$i) {
            $bit = $bits[$i];
            $node = ($bit === '0') ? $node->left : $node->right;
            if ($node === null) {
                throw new RuntimeException('Corrupted bitstream.');
            }
            if ($node->isLeaf()) {
                $result .= $node->char;
                $node = $root;
            }
        }

        // In case of a single‑symbol source the loop never moves; handle leftover.
        if ($node !== $root && $node->isLeaf()) {
            $result .= $node->char;
        }

        return $result;
    }
}
