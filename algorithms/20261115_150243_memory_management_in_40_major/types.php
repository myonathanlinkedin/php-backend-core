<?php
declare(strict_types=1);

/**
 * Represents memory management statistics for a single C project.
 */
final class ProjectMemoryStats
{
    /** @var string */
    private string $projectName;

    /** @var int Total number of allocation calls (e.g., malloc, calloc, realloc) */
    private int $allocationCount;

    /** @var int Total number of free calls */
    private int $freeCount;

    /** @var int Peak memory usage observed during profiling, in kilobytes */
    private int $peakMemoryKB;

    /**
     * @var array<string,int> Mapping from allocation function name to its call count.
     * Example: ['malloc' => 120, 'calloc' => 30]
     */
    private array $allocationFunctions;

    public function __construct(
        string $projectName,
        int $allocationCount,
        int $freeCount,
        int $peakMemoryKB,
        array $allocationFunctions
    ) {
        $this->projectName = $projectName;
        $this->allocationCount = $allocationCount;
        $this->freeCount = $freeCount;
        $this->peakMemoryKB = $peakMemoryKB;
        $this->allocationFunctions = $allocationFunctions;
    }

    public function getProjectName(): string
    {
        return $this->projectName;
    }

    public function getAllocationCount(): int
    {
        return $this->allocationCount;
    }

    public function getFreeCount(): int
    {
        return $this->freeCount;
    }

    public function getPeakMemoryKB(): int
    {
        return $this->peakMemoryKB;
    }

    /**
     * @return array<string,int>
     */
    public function getAllocationFunctions(): array
    {
        return $this->allocationFunctions;
    }

    /**
     * Returns the ratio of frees to allocations.
     * Value in [0,1]; 1 means every allocation was freed.
     */
    public function getFreeToAllocRatio(): float
    {
        if ($this->allocationCount === 0) {
            return 0.0;
        }
        return $this->freeCount / $this->allocationCount;
    }
}
