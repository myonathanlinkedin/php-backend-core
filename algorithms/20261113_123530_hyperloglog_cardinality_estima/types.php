<?php
declare(strict_types=1);

/**
 * Configuration for HyperLogLog.
 */
final class HyperLogLogConfig
{
    /** @var int Precision (number of bits for register index) */
    public int $precision;

    /**
     * @param int $precision Must be between 4 and 16 inclusive.
     */
    public function __construct(int $precision = 12)
    {
        if ($precision < 4 || $precision > 16) {
            throw new InvalidArgumentException('Precision must be between 4 and 16.');
        }
        $this->precision = $precision;
    }

    /**
     * Number of registers m = 2^p.
     */
    public function getRegisterCount(): int
    {
        return 1 << $this->precision;
    }
}

/**
 * Interface for hash functions used by HyperLogLog.
 */
interface HyperLogLogHash
{
    /**
     * Returns a 64‑bit unsigned integer hash of the given string.
     */
    public function hash(string $value): int;
}

/**
 * Default hash implementation using SHA‑256 and extracting the first 64 bits.
 */
final class Sha256Hash implements HyperLogLogHash
{
    public function hash(string $value): int
    {
        // Get raw binary SHA‑256 (32 bytes)
        $binary = hash('sha256', $value, true);
        // Extract first 8 bytes (64 bits) as two 32‑bit unsigned integers
        /** @var array<string, int> $parts */
        $parts = unpack('N2', $binary);
        // Combine to 64‑bit unsigned integer (PHP int is 64‑bit on modern platforms)
        $high = $parts[1];
        $low  = $parts[2];
        // Use bitwise operations; ensure unsigned behavior
        $hash = ($high << 32) | $low;
        // PHP may treat the result as signed; convert to positive using sprintf
        if ($hash < 0) {
            $hash = (int)sprintf('%u', $hash);
        }
        return $hash;
    }
}

/**
 * Core HyperLogLog data structure.
 */
final class HyperLogLog
{
    private HyperLogLogConfig $config;
    /** @var array<int, int> Register array, each entry holds a small integer */
    private array $registers;
    private HyperLogLogHash $hasher;

    /**
     * @param HyperLogLogConfig $config
     * @param HyperLogLogHash|null $hasher Optional custom hash implementation.
     */
    public function __construct(HyperLogLogConfig $config, ?HyperLogLogHash $hasher = null)
    {
        $this->config   = $config;
        $this->registers = array_fill(0, $config->getRegisterCount(), 0);
        $this->hasher    = $hasher ?? new Sha256Hash();
    }

    /**
     * Adds an element to the sketch.
     */
    public function add(string $element): void
    {
        $hash = $this->hasher->hash($element);
        $p    = $this->config->precision;
        $m    = $this->config->getRegisterCount();

        // Register index: lower p bits
        $idx = $hash & ($m - 1);

        // Remaining bits after index
        $w = $hash >> $p;
        $rank = $this->rho($w, 64 - $p);

        if ($rank > $this->registers[$idx]) {
            $this->registers[$idx] = $rank;
        }
    }

    /**
     * Returns the current cardinality estimate.
     */
    public function estimate(): float
    {
        $m = $this->config->getRegisterCount();
        $alpha = $this->getAlpha($m);

        $sum = 0.0;
        foreach ($this->registers as $register) {
            $sum += 2.0 ** (-$register);
        }

        $rawEstimate = $alpha * $m * $m / $sum;

        // Small range correction (Linear Counting)
        if ($rawEstimate <= (5.0 / 2.0) * $m) {
            $zeroRegisters = count(array_filter($this->registers, fn($v) => $v === 0));
            if ($zeroRegisters !== 0) {
                $rawEstimate = $m * log($m / $zeroRegisters);
            }
        }

        // Large range correction (not needed for typical 64‑bit hash)
        $twoPow64 = 2.0 ** 64;
        if ($rawEstimate > $twoPow64 / 30.0) {
            $rawEstimate = -$twoPow64 * log(1.0 - $rawEstimate / $twoPow64);
        }

        return $rawEstimate;
    }

    /**
     * Merges another HyperLogLog into this one (union operation).
     *
     * @throws InvalidArgumentException if configurations differ.
     */
    public function merge(HyperLogLog $other): void
    {
        if ($this->config->precision !== $other->config->precision) {
            throw new InvalidArgumentException('Cannot merge HyperLogLog instances with different precisions.');
        }

        foreach ($this->registers as $i => $value) {
            if ($other->registers[$i] > $value) {
                $this->registers[$i] = $other->registers[$i];
            }
        }
    }

    /**
     * Returns a copy of the internal registers (for testing).
     *
     * @return array<int, int>
     */
    public function getRegisters(): array
    {
        return $this->registers;
    }

    /**
     * Helper: count leading zeros plus one (rho function).
     *
     * @param int $value The bits to examine.
     * @param int $maxBits Maximum bits considered.
     */
    private function rho(int $value, int $maxBits): int
    {
        $rank = 1;
        while ($rank <= $maxBits && ($value & (1 << ($maxBits - $rank))) === 0) {
            $rank++;
        }
        return $rank;
    }

    /**
     * Returns the bias correction constant alpha_m.
     */
    private function getAlpha(int $m): float
    {
        return match ($m) {
            16   => 0.673,
            32   => 0.697,
            64   => 0.709,
            default => 0.7213 / (1 + 1.079 / $m),
        };
    }
}
?>
