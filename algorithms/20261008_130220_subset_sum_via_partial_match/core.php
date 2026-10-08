<?php

class SubsetSumSolver
{
    /**
     * Determines whether any subset of $numbers sums to $target.
     *
     * @param array<int> $numbers
     * @param int $target
     * @return bool
     */
    public function hasSubsetSum(array $numbers, int $target): bool
    {
        // Empty set: only sum 0 is achievable.
        if (empty($numbers)) {
            return $target === 0;
        }

        // Quick check for target 0: empty subset always works.
        if ($target === 0) {
            return true;
        }

        $n = count($numbers);
        $mid = intdiv($n, 2);
        $firstHalf = array_slice($numbers, 0, $mid);
        $secondHalf = array_slice($numbers, $mid);

        $sumsFirst = $this->getSubsetSums($firstHalf);
        $sumsSecond = $this->getSubsetSums($secondHalf);
        sort($sumsSecond, SORT_NUMERIC);

        foreach ($sumsFirst as $sum) {
            $complement = $target - $sum;
            if ($this->binarySearch($sumsSecond, $complement)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates all possible subset sums of the given array.
     *
     * @param array<int> $arr
     * @return array<int>
     */
    private function getSubsetSums(array $arr): array
    {
        $n = count($arr);
        $totalMasks = 1 << $n;
        $sums = [];

        for ($mask = 0; $mask < $totalMasks; $mask++) {
            $sum = 0;
            for ($i = 0; $i < $n; $i++) {
                if ($mask & (1 << $i)) {
                    $sum += $arr[$i];
                }
            }
            $sums[] = $sum;
        }

        return $sums;
    }

    /**
     * Binary search for $value in sorted array $arr.
     *
     * @param array<int> $arr
     * @param int $value
     * @return bool
     */
    private function binarySearch(array $arr, int $value): bool
    {
        $low = 0;
        $high = count($arr) - 1;

        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            if ($arr[$mid] === $value) {
                return true;
            } elseif ($arr[$mid] < $value) {
                $low = $mid + 1;
            } else {
                $high = $mid - 1;
            }
        }

        return false;
    }
}
