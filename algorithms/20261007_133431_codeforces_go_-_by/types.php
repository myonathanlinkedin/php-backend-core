<?php
declare(strict_types=1);

/**
 * Interface for a Disjoint Set Union (Union-Find) data structure.
 */
interface SetUnionFind
{
    /**
     * Finds the representative (root) of the set containing $element.
     *
     * @param int $element
     * @return int Representative element.
     */
    public function find(int $element): int;

    /**
     * Unites the sets containing $a and $b.
     *
     * @param int $a
     * @param int $b
     * @return void
     */
    public function union(int $a, int $b): void;

    /**
     * Checks whether $a and $b belong to the same set.
     *
     * @param int $a
     * @param int $b
     * @return bool
     */
    public function connected(int $a, int $b): bool;
}
