<?php
declare(strict_types=1);

final class Task
{
    public string $id;
    public int $hardwareCost;
    public int $softwareCost;
    public int $benefit;

    public function __construct(string $id, int $hardwareCost, int $softwareCost, int $benefit)
    {
        $this->id = $id;
        $this->hardwareCost = $hardwareCost;
        $this->softwareCost = $softwareCost;
        $this->benefit = $benefit;
    }
}

final class PartitionResult
{
    /** @var array<string, string> */
    public array $assignments; // 'HW', 'SW', or 'NONE'
    public int $totalBenefit;
    public int $usedHardware;
    public int $usedSoftware;

    public function __construct(array $assignments, int $totalBenefit, int $usedHardware, int $usedSoftware)
    {
        $this->assignments = $assignments;
        $this->totalBenefit = $totalBenefit;
        $this->usedHardware = $usedHardware;
        $this->usedSoftware = $usedSoftware;
    }
}

final class PartitionSolver
{
    /**
     * Solve the exact hardware-software partitioning problem.
     *
     * @param Task[] $tasks
     * @param int $hardwareBudget
     * @param int $softwareBudget
     * @return PartitionResult
     */
    public function solve(array $tasks, int $hardwareBudget, int $softwareBudget): PartitionResult
    {
        $n = count($tasks);
        // DP table: benefit, initialized to -1 (unreachable)
        $dp = array_fill(0, $hardwareBudget + 1, array_fill(0, $softwareBudget + 1, -1));
        // predecessor info for backtracking
        $prev = array_fill(0, $hardwareBudget + 1, array_fill(0, $softwareBudget + 1, null));

        $dp[0][0] = 0; // base case

        foreach ($tasks as $idx => $task) {
            // iterate budgets descending to avoid reuse within same iteration
            for ($h = $hardwareBudget; $h >= 0; $h--) {
                for ($s = $softwareBudget; $s >= 0; $s--) {
                    if ($dp[$h][$s] < 0) {
                        continue; // unreachable state
                    }

                    // Option 1: assign to hardware
                    $newH = $h + $task->hardwareCost;
                    $newS = $s;
                    if ($newH <= $hardwareBudget) {
                        $newBenefit = $dp[$h][$s] + $task->benefit;
                        if ($newBenefit > $dp[$newH][$newS]) {
                            $dp[$newH][$newS] = $newBenefit;
                            $prev[$newH][$newS] = ['h' => $h, 's' => $s, 'idx' => $idx, 'mode' => 'HW'];
                        }
                    }

                    // Option 2: assign to software
                    $newH = $h;
                    $newS = $s + $task->softwareCost;
                    if ($newS <= $softwareBudget) {
                        $newBenefit = $dp[$h][$s] + $task->benefit;
                        if ($newBenefit > $dp[$newH][$newS]) {
                            $dp[$newH][$newS] = $newBenefit;
                            $prev[$newH][$newS] = ['h' => $h, 's' => $s, 'idx' => $idx, 'mode' => 'SW'];
                        }
                    }

                    // Option 3: skip task (implicitly handled by keeping current state)
                }
            }
        }

        // Find best reachable state
        $bestBenefit = -1;
        $bestH = 0;
        $bestS = 0;
        for ($h = 0; $h <= $hardwareBudget; $h++) {
            for ($s = 0; $s <= $softwareBudget; $s++) {
                if ($dp[$h][$s] > $bestBenefit) {
                    $bestBenefit = $dp[$h][$s];
                    $bestH = $h;
                    $bestS = $s;
                }
            }
        }

        // Reconstruct assignments
        $assignments = [];
        foreach ($tasks as $task) {
            $assignments[$task->id] = 'NONE';
        }

        $curH = $bestH;
        $curS = $bestS;
        while ($prev[$curH][$curS] !== null) {
            $info = $prev[$curH][$curS];
            $task = $tasks[$info['idx']];
            $assignments[$task->id] = $info['mode'];
            $curH = $info['h'];
            $curS = $info['s'];
        }

        return new PartitionResult($assignments, $bestBenefit, $bestH, $bestS);
    }
}
