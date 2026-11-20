<?php
declare(strict_types=1);

/**
 * Represents a single node within a Trie.
 */
final class TrieNode
{
    /** @var array<string, TrieNode> Child nodes indexed by character */
    public array $children = [];

    /** @var bool Indicates if this node terminates a valid word */
    public bool $isEnd = false;

    /** @var int Frequency count for the word ending at this node */
    public int $frequency = 0;

    /** @var string The word stored at this node (valid only if $isEnd is true) */
    public string $word = '';

    public function __construct()
    {
        $this->children = [];
        $this->isEnd = false;
        $this->frequency = 0;
        $this->word = '';
    }
}

/**
 * Result entry for auto‑completion suggestions.
 */
final class Suggestion
{
    public string $word;
    public int $frequency;

    public function __construct(string $word, int $frequency)
    {
        $this->word = $word;
        $this->frequency = $frequency;
    }
}
