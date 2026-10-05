<?php
declare(strict_types=1);

namespace CpAlgorithms;

/**
 * Core algorithmic primitives and data structures.
 * Implements O(1) amortized stack, O(log n) binary search, and O(n log n) sorting.
 */
final class Core
{
    /**
     * @var array<int, mixed>
     */
    private array $stack = [];

    /**
     * Push an element onto the stack.
     * Time Complexity: O(1)
     */
    public function push(mixed $value): void
    {
        $this->stack[] = $value;
    }

    /**
     * Pop an element from the stack.
     * Time Complexity: O(1)
     * @throws \RuntimeException If stack is empty.
     */
    public function pop(): mixed
    {
        if (empty($this->stack)) {
            throw new \RuntimeException("Stack underflow: cannot pop from empty stack.");
        }
        return array_pop($this->stack);
    }

    /**
     * Peek at the top element without removing it.
     * Time Complexity: O(1)
     * @throws \RuntimeException If stack is empty.
     */
    public function peek(): mixed
    {
        if (empty($this->stack)) {
            throw new \RuntimeException("Stack underflow: cannot peek at empty stack.");
        }
        return end($this->stack);
    }

    /**
     * Check if the stack is empty.
     * Time Complexity: O(1)
     */
    public function isEmpty(): bool
    {
        return empty($this->stack);
    }

    /**
     * Get the current size of the stack.
     * Time Complexity: O(1)
     */
    public function size(): int
    {
        return count($this->stack);
    }

    /**
     * Binary search for a target value in a sorted array.
     * Time Complexity: O(log n)
     * Space Complexity: O(1)
     *
     * @param array<int, int|float> $sortedArray The input array must be sorted in ascending order.
     * @param int|float $target The value to search for.
     * @return int The index of the target if found, otherwise -1.
     */
    public static function binarySearch(array $sortedArray, int|float $target): int
    {
        $left = 0;
        $right = count($sortedArray) - 1;

        while ($left <= $right) {
            // Prevent integer overflow in index calculation
            $mid = $left + intdiv($right - $left, 2);
            $midValue = $sortedArray[$mid];

            if ($midValue === $target) {
                return $mid;
            } elseif ($midValue < $target) {
                $left = $mid + 1;
            } else {
                $right = $mid - 1;
            }
        }

        return -1;
    }

    /**
     * Merge sort implementation.
     * Time Complexity: O(n log n)
     * Space Complexity: O(n)
     *
     * @param array<int, int|float> $array The input array.
     * @return array<int, int|float> The sorted array.
     */
    public static function mergeSort(array $array): array
    {
        $n = count($array);
        if ($n <= 1) {
            return $array;
        }

        $mid = intdiv($n, 2);
        $left = self::mergeSort(array_slice($array, 0, $mid));
        $right = self::mergeSort(array_slice($array, $mid));

        return self::merge($left, $right);
    }

    /**
     * Merge two sorted arrays into a single sorted array.
     * Time Complexity: O(n + m)
     *
     * @param array<int, int|float> $left
     * @param array<int, int|float> $right
     * @return array<int, int|float>
     */
    private static function merge(array $left, array $right): array
    {
        $result = [];
        $i = 0;
        $j = 0;
        $lenLeft = count($left);
        $lenRight = count($right);

        while ($i < $lenLeft && $j < $lenRight) {
            if ($left[$i] <= $right[$j]) {
                $result[] = $left[$i++];
            } else {
                $result[] = $right[$j++];
            }
        }

        while ($i < $lenLeft) {
            $result[] = $left[$i++];
        }

        while ($j < $lenRight) {
            $result[] = $right[$j++];
        }

        return $result;
    }
}
