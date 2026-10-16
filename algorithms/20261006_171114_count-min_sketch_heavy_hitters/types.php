<?php

interface ICountMinSketch
{
    public function add(string $item, int $count = 1): void;
    public function estimate(string $item): int;
    public function getHeavyHitters(array $items, int $threshold): array;
}

final class CountMinSketch implements ICountMinSketch
{
    private int $width;
    private int $depth;
    /** @var array<int, array<int, int>> */
    private array $table;
    /** @var array<int, int> */
    private array $seeds;
    /** @var array<string, int> */
    private array $itemSet = [];

    public function __construct(float $epsilon, float $delta)
    {
        if ($epsilon <= 0 || $epsilon >= 1) {
            throw new InvalidArgumentException('Epsilon must be between 0 and 1.');
        }
        if ($delta <= 0 || $delta >= 1) {
            throw new InvalidArgumentException('Delta must be between 0 and 1.');
        }
        $this->width  = (int) ceil(2.718281828459045 / $epsilon);
        $this->depth  = (int) ceil(log(1 / $delta, 2));
        $this->table  = array_fill(0, $this->depth, array_fill(0, $this->width, 0));
        $this->seeds  = [];
        for ($i = 0; $i < $this->depth; $i++) {
            $this->seeds[$i] = random_int(1, PHP_INT_MAX);
        }
    }

    public function add(string $item, int $count = 1): void
    {
        if ($count <= 0) {
            throw new InvalidArgumentException('Count must be positive.');
        }
        $this->itemSet[$item] = 1;
        for ($i = 0; $i < $this->depth; $i++) {
            $index = $this->hashIndex($item, $this->seeds[$i]);
            $this->table[$i][$index] += $count;
        }
    }

    public function estimate(string $item): int
    {
        $min = PHP_INT_MAX;
        for ($i = 0; $i < $this->depth; $i++) {
            $index = $this->hashIndex($item, $this->seeds[$i]);
            $min = min($min, $this->table[$i][$index]);
        }
        return $min;
    }

    public function getHeavyHitters(array $items, int $threshold): array
    {
        $heavy = [];
        foreach ($items as $item) {
            if ($this->estimate($item) >= $threshold) {
                $heavy[] = $item;
            }
        }
        return $heavy;
    }

    private function hashIndex(string $item, int $seed): int
    {
        $hash = crc32($item . $seed);
        return abs($hash) % $this->width;
    }
}
