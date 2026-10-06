<?php
declare(strict_types=1);

/**
 * Domain models and memory structures for the Nusku continuous profiler.
 * 
 * This module defines the core data structures required to represent
 * profiling sessions, stack traces, and statistical aggregates.
 */

/**
 * Represents a single stack frame in a call stack.
 */
final class StackFrame
{
    public function __construct(
        public readonly string $functionName,
        public readonly string $fileName,
        public readonly int $lineNumber,
        public readonly int $address
    ) {}
}

/**
 * Represents a complete stack trace captured at a specific point in time.
 */
final class StackTrace
{
    /**
     * @param StackFrame[] $frames
     */
    public function __construct(
        public readonly array $frames,
        public readonly int $timestamp
    ) {}

    /**
     * Generates a unique hash for this stack trace to facilitate aggregation.
     */
    public function getHash(): string
    {
        $parts = [];
        foreach ($this->frames as $frame) {
            $parts[] = sprintf("%s@%s:%d", $frame->functionName, $frame->fileName, $frame->lineNumber);
        }
        return md5(implode('->', $parts));
    }
}

/**
 * Represents an aggregated profile entry for a specific stack trace.
 */
final class ProfileEntry
{
    public function __construct(
        public readonly StackTrace $trace,
        public int $count = 0,
        public int $totalTime = 0
    ) {}

    /**
     * Updates the entry with new sampling data.
     */
    public function addSample(int $duration): void
    {
        $this->count++;
        $this->totalTime += $duration;
    }
}

/**
 * Configuration for the profiling session.
 */
final class ProfilerConfig
{
    public function __construct(
        public readonly int $sampleRate = 100, // Hz
        public readonly int $maxStackDepth = 128,
        public readonly bool $enableDwarf = true
    ) {}
}

/**
 * Represents the state of a profiling session.
 */
final class ProfilingSession
{
    private array $entries = [];
    private int $totalSamples = 0;
    private int $startTime = 0;
    private int $endTime = 0;

    public function __construct(
        public readonly ProfilerConfig $config
    ) {}

    public function start(): void
    {
        $this->startTime = (int) (microtime(true) * 1000000);
        $this->entries = [];
        $this->totalSamples = 0;
    }

    public function stop(): void
    {
        $this->endTime = (int) (microtime(true) * 1000000);
    }

    public function addSample(StackTrace $trace, int $duration): void
    {
        $hash = $trace->getHash();
        if (!isset($this->entries[$hash])) {
            $this->entries[$hash] = new ProfileEntry($trace);
        }
        $this->entries[$hash]->addSample($duration);
        $this->totalSamples++;
    }

    /**
     * @return ProfileEntry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    public function getTotalSamples(): int
    {
        return $this->totalSamples;
    }

    public function getDuration(): int
    {
        return $this->endTime - $this->startTime;
    }
}
