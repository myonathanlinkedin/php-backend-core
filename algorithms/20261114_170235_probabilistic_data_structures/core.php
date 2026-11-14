<?php
declare(strict_types=1);

/**
 * BloomFilter implements a classic probabilistic set membership data structure.
 *
 * @see https://en.wikipedia.org/wiki/Bloom_filter
 */
final class BloomFilter
{
    /** @var int Number of bits in the filter */
    private int $size;

    /** @var int Number of hash functions */
    private int $hashCount;

    /** @var string Bit array stored as a binary string */
    private string $bits;

    /**
     * BloomFilter constructor.
     *
     * @param int $size      Number of bits (must be > 0)
     * @param int $hashCount Number of hash functions (must be > 0)
     *
     * @throws InvalidArgumentException on invalid parameters
     */
    public function __construct(int $size, int $hashCount)
    {
        if ($size <= 0) {
            throw new InvalidArgumentException('Size must be positive');
        }
        if ($hashCount <= 0) {
            throw new InvalidArgumentException('Hash count must be positive');
        }
        $this->size      = $size;
        $this->hashCount = $hashCount;
        $byteLength      = intdiv($size + 7, 8);
        $this->bits      = str_repeat("\0", $byteLength);
    }

    /**
     * Adds an element to the filter.
     *
     * @param string $item Element to add
     */
    public function add(string $item): void
    {
        foreach ($this->hashes($item) as $index) {
            $this->setBit($index);
        }
    }

    /**
     * Checks whether an element is possibly in the filter.
     *
     * @param string $item Element to test
     *
     * @return bool True if possibly present, false if definitely not present
     */
    public function contains(string $item): bool
    {
        foreach ($this->hashes($item) as $index) {
            if (!$this->getBit($index)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Estimates the false‑positive probability after inserting $n items.
     *
     * @param int $n Number of inserted items
     *
     * @return float Probability in [0,1]
     */
    public function falsePositiveProbability(int $n): float
    {
        if ($n < 0) {
            throw new InvalidArgumentException('Item count cannot be negative');
        }
        $k = $this->hashCount;
        $m = $this->size;
        // Formula: (1 - e^(-k * n / m)) ^ k
        $exponent = -$k * $n / $m;
        $prob     = pow(1 - exp($exponent), $k);
        // Clamp due to floating‑point rounding
        return min(max($prob, 0.0), 1.0);
    }

    /**
     * Generates $hashCount indices for $item using double hashing.
     *
     * @param string $item
     *
     * @return iterable<int>
     */
    private function hashes(string $item): iterable
    {
        // First hash: unsigned 32‑bit CRC32
        $h1 = crc32($item);
        // Second hash: CRC32 of item with a fixed salt to ensure independence
        $h2 = crc32($item . "\x00");

        // Ensure $h2 is odd to guarantee full cycle (common practice)
        $h2 = $h2 | 1;

        for ($i = 0; $i < $this->hashCount; ++$i) {
            // 64‑bit arithmetic is safe in PHP 8 on 64‑bit platforms;
            // we mask to keep the value non‑negative.
            $combined = ($h1 + $i * $h2) & 0x7fffffff;
            $index    = $combined % $this->size;
            yield $index;
        }
    }

    /**
     * Sets the bit at position $index to 1.
     *
     * @param int $index Bit index (0‑based)
     */
    private function setBit(int $index): void
    {
        $bytePos = intdiv($index, 8);
        $bitPos  = $index % 8;
        $mask    = 1 << $bitPos;
        $byte    = $this->bits[$bytePos];
        $this->bits[$bytePos] = $byte | chr($mask);
    }

    /**
     * Retrieves the bit at position $index.
     *
     * @param int $index Bit index (0‑based)
     *
     * @return bool True if bit is 1, false otherwise
     */
    private function getBit(int $index): bool
    {
        $bytePos = intdiv($index, 8);
        $bitPos  = $index % 8;
        $mask    = 1 << $bitPos;
        $byte    = $this->bits[$bytePos];
        return (ord($byte) & $mask) !== 0;
    }
}
