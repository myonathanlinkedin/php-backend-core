<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Aggregates memory statistics across many projects.
 */
final class MemorySurvey
{
    /** @var array<string,ProjectMemoryStats> */
    private array $projects = [];

    /**
     * Adds a project's statistics to the survey.
     *
     * If a project with the same name already exists, it is overwritten.
     */
    public function addProject(ProjectMemoryStats $stats): void
    {
        $this->projects[$stats->getProjectName()] = $stats;
    }

    /**
     * @return int Total number of projects in the survey.
     */
    public function getProjectCount(): int
    {
        return count($this->projects);
    }

    /**
     * @return int Sum of allocation calls across all projects.
     */
    public function getTotalAllocations(): int
    {
        $sum = 0;
        foreach ($this->projects as $p) {
            $sum += $p->getAllocationCount();
        }
        return $sum;
    }

    /**
     * @return int Sum of free calls across all projects.
     */
    public function getTotalFrees(): int
    {
        $sum = 0;
        foreach ($this->projects as $p) {
            $sum += $p->getFreeCount();
        }
        return $sum;
    }

    /**
     * Returns the overall free‑to‑allocation ratio across the whole survey.
     */
    public function getOverallFreeToAllocRatio(): float
    {
        $totalAlloc = $this->getTotalAllocations();
        if ($totalAlloc === 0) {
            return 0.0;
        }
        return $this->getTotalFrees() / $totalAlloc;
    }

    /**
     * @return float Average peak memory (KB) across all projects.
     * Returns 0.0 when there are no projects.
     */
    public function getAveragePeakMemoryKB(): float
    {
        $count = $this->getProjectCount();
        if ($count === 0) {
            return 0.0;
        }
        $sum = 0;
        foreach ($this->projects as $p) {
            $sum += $p->getPeakMemoryKB();
        }
        return $sum / $count;
    }

    /**
     * Determines the most frequently used allocation function across the survey.
     *
     * @return string|null Function name, or null if no allocation data exists.
     */
    public function getMostCommonAllocationFunction(): ?string
    {
        $globalCounts = [];
        foreach ($this->projects as $p) {
            foreach ($p->getAllocationFunctions() as $func => $cnt) {
                if (!isset($globalCounts[$func])) {
                    $globalCounts[$func] = 0;
                }
                $globalCounts[$func] += $cnt;
            }
        }
        if (empty($globalCounts)) {
            return null;
        }
        // Find max count; in case of tie, return lexicographically smallest name.
        $maxCount = max($globalCounts);
        $candidates = array_keys(array_filter(
            $globalCounts,
            fn(int $c) => $c === $maxCount
        ));
        sort($candidates, SORT_STRING);
        return $candidates[0];
    }

    /**
     * Retrieves a project's statistics by name.
     *
     * @return ProjectMemoryStats|null
     */
    public function getProject(string $name): ?ProjectMemoryStats
    {
        return $this->projects[$name] ?? null;
    }
}
