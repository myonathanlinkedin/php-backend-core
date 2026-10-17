<?php
declare(strict_types=1);

/**
 * Interface defining the contract for a Union-Find (Disjoint Set Union) data structure.
 * The implementation uses path compression and union by rank to achieve near‑constant amortized time.
 */
interface UnionFindInterface
{
    /**
     * Initialize a Union-Find structure with a given number of elements (0 … $size‑1).
     *
     * @param int $size Number of distinct elements.
     * @throws InvalidArgumentException if $size is negative.
     */
    public function __construct(int $size);

    /**
     * Find the representative (root) of the set containing element $x.
     *
     * @param int $x Element index.
     * @return int Representative of the set.
     * @throws OutOfBoundsException if $x is outside the valid range.
     */
    public function find(int $x): int;

    /**
     * Merge the sets containing $a and $b.
     *
     * @param int $a First element.
     * @param int $b Second element.
     * @throws OutOfBoundsException if either argument is outside the valid range.
     */
    public function union(int $a, int $b): void;

    /**
     * Determine whether $a and $b belong to the same set.
     *
     * @param int $a First element.
     * @param int $b Second element.
     * @return bool True if both elements are connected.
     * @throws OutOfBoundsException if either argument is outside the valid range.
     */
    public function connected(int $a, int $b): bool;

    /**
     * Return the current number of distinct components (sets).
     *
     * @return int Number of components.
     */
    public function getComponentCount(): int;
}
