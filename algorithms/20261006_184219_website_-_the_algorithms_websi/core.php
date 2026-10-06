<?php

/**
 * Represents a single algorithm entry.
 */
final class Algorithm
{
    private string $name;
    private string $description;
    private array $tags;
    private string $sourceUrl;

    public function __construct(
        string $name,
        string $description,
        array $tags,
        string $sourceUrl
    ) {
        $this->name        = $name;
        $this->description = $description;
        $this->tags        = $tags;
        $this->sourceUrl   = $sourceUrl;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getSourceUrl(): string
    {
        return $this->sourceUrl;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }
}

/**
 * In-memory library of algorithms.
 */
final class AlgorithmLibrary
{
    /** @var array<string, Algorithm> */
    private array $algorithms = [];

    public function addAlgorithm(Algorithm $algorithm): void
    {
        $this->algorithms[$algorithm->getName()] = $algorithm;
    }

    public function removeAlgorithm(string $name): bool
    {
        if (!isset($this->algorithms[$name])) {
            return false;
        }
        unset($this->algorithms[$name]);
        return true;
    }

    public function findByName(string $name): ?Algorithm
    {
        return $this->algorithms[$name] ?? null;
    }

    /**
     * @return Algorithm[]
     */
    public function findByTag(string $tag): array
    {
        $result = [];
        foreach ($this->algorithms as $algorithm) {
            if ($algorithm->hasTag($tag)) {
                $result[] = $algorithm;
            }
        }
        return $result;
    }

    /**
     * @return Algorithm[]
     */
    public function listAll(): array
    {
        return array_values($this->algorithms);
    }

    /**
     * @return Algorithm[]
     */
    public function listByCategory(string $category): array
    {
        return $this->findByTag($category);
    }
}
