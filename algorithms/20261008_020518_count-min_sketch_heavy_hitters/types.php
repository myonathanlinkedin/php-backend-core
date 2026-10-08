<?php
declare(strict_types=1);

/**
 * Interface for frequency estimation data structures.
 */
interface FrequencyEstimator
{
    /**
     * Increment the count for a given item.
     *
     * @param string $item
     * @param int    $increment
     */
    public function add(string $item, int $increment = 1): void;

    /**
     * Estimate the frequency of an item.
     *
     * @param string $item
     *
     * @return int
     */
    public function estimate(string $item): int;

    /**
     * Return the total number of increments added to the structure.
     *
     * @return int
     */
    public function total(): int;

    /**
     * Return items whose estimated frequency exceeds the given fraction of the total.
     *
     * @param float $phi Fraction between 0 and 1 (e.g., 0.01 for 1% heavy hitters)
     *
     * @return array<string,int> Map of item => estimated frequency
     */
    public function getHeavyHitters(float $phi): array;
}

/**
 * Simple data holder for a heavy hitter entry.
 */
final class HeavyHitter
{
    /** @var string */
    public $item;
    /** @var int */
    public $estimate;

    public function __construct(string $item, int $estimate)
    {
        $this->item = $item;
        $this->estimate = $estimate;
    }
}
