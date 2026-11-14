<?php
declare(strict_types=1);

/**
 * BloomFilter - a space-efficient probabilistic set membership data structure.
 *
 * This implementation uses a bit array stored as a binary string and a configurable
 * number of hash functions derived from SHA-256. It provides O(k) add and query,
 * where k is the number of hash functions.
 *
 * @author  Autonomous Systems Engineer
 * @license MIT
 */
final class BloomFilter
{
    /** @var int Number of bits in the filter */
    private int $size;

    /** @var int Number of hash functions */
    private int $hashCount;

    /** @var string Bit array stored as binary string */
    private string $bits;

    /**
     * Constructor.
     *
     * @param int $size      Number of bits (must be > 0)
     * @param int $hashCount Number of hash functions (must be > 0)
     *
     * @throws InvalidArgumentException
     */
    public function __construct(int $size, int $hashCount)
    {
        if ($size <= 0) {
            throw new InvalidArgumentException('Size must be a positive integer.');
        }
        if ($hashCount <= 0) {
            throw new InvalidArgumentException('Hash count must be a positive integer.');
        }
        $this->size      = $size;
        $this->hashCount = $hashCount;
        // Initialise bit array with all bits cleared.
        $byteLength      = intdiv($size + 7, 8);
        $this->bits      = str_repeat("\0", $byteLength);
    }

    /**
     * Add an element to the filter.
     *
     * @param string $item Element to add.
     */
    public function add(string $item): void
    {
        foreach ($this->hashIndices($item) as $idx) {
            $this->setBit($idx);
        }
    }

    /**
     * Test whether an element is possibly in the filter.
     *
     * @param string $item Element to test.
     *
     * @return bool True if possibly present, false if definitely not present.
     */
    public function contains(string $item): bool
    {
        foreach ($this->hashIndices($item) as $idx) {
            if (!$this->getBit($idx)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Estimate the false positive probability after inserting $n items.
     *
     * @param int $n Number of inserted items.
     *
     * @return float Estimated probability (0.0–1.0).
     *
     * @throws InvalidArgumentException
     */
    public function estimateFalsePositiveProbability(int $n): float
    {
        if ($n < 0) {
            throw new InvalidArgumentException('Number of items must be non‑negative.');
        }
        $k = $this->hashCount;
        $m = $this->size;
        // Standard Bloom filter formula: (1 - e^{-kn/m})^k
        $exp = -($k * $n) / $m;
        $prob = pow(1 - exp($exp), $k);
        // Clamp due to floating‑point rounding.
        return min(max($prob, 0.0), 1.0);
    }

    /**
     * Compute the k hash indices for a given item.
     *
     * @param string $item Input string.
     *
     * @return iterable<int> Sequence of bit positions in [0, size).
     */
    private function hashIndices(string $item): iterable
    {
        // Derive a base hash and then generate k variations.
        $baseHash = hash('sha256', $item, true); // raw binary
        // Split the 256‑bit hash into 4 64‑bit chunks.
        $chunks = str_split($baseHash, 8);
        $a = $this->bytesToUint64($chunks[0] ?? "\0");
        $b = $this->bytesToUint64($chunks[1] ?? "\0");

        for ($i = 0; $i < $this->hashCount; $i++) {
            // Double‑hashing technique: (a + i * b) mod m
            $combined = ($a + $i * $b) & 0xFFFFFFFFFFFFFFFF;
            $idx = $combined % $this->size;
            yield $idx;
        }
    }

    /**
     * Convert up to 8 binary bytes to an unsigned 64‑bit integer.
     *
     * @param string $bytes Binary string (length ≤ 8).
     *
     * @return int Unsigned integer (may overflow PHP int on 32‑bit builds, but works for modulo arithmetic).
     */
    private function bytesToUint64(string $bytes): int
    {
        $hex = bin2hex(str_pad($bytes, 8, "\0", STR_PAD_RIGHT));
        // Use base 16 conversion; PHP int is signed but modulo works correctly for our use.
        return (int)hexdec($hex);
    }

    /**
     * Set the bit at position $idx to 1.
     *
     * @param int $idx Bit index.
     */
    private function setBit(int $idx): void
    {
        $bytePos = intdiv($idx, 8);
        $bitPos  = $idx % 8;
        $mask    = 1 << $bitPos;
        $byte    = $this->bits[$bytePos];
        $this->bits[$bytePos] = $byte | chr($mask);
    }

    /**
     * Retrieve the bit at position $idx.
     *
     * @param int $idx Bit index.
     *
     * @return bool True if bit is 1, false otherwise.
     */
    private function getBit(int $idx): bool
    {
        $bytePos = intdiv($idx, 8);
        $bitPos  = $idx % 8;
        $mask    = 1 << $bitPos;
        $byte    = $this->bits[$bytePos];
        return (ord($byte) & $mask) !== 0;
    }
}
