<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Trie data structure supporting insertion and prefix‑based auto‑completion
 * with frequency ranking.
 */
final class Trie
{
    private TrieNode $root;

    public function __construct()
    {
        $this->root = new TrieNode();
    }

    /**
     * Inserts a word into the trie, optionally adding to its frequency.
     *
     * @param string $word      Word to insert (case‑insensitive, trimmed)
     * @param int    $frequency Frequency increment (must be >0)
     *
     * @return void
     */
    public function insert(string $word, int $frequency = 1): void
    {
        $word = trim(strtolower($word));
        if ($word === '' || $frequency <= 0) {
            return;
        }

        $node = $this->root;
        $len = strlen($word);
        for ($i = 0; $i < $len; $i++) {
            $ch = $word[$i];
            if (!isset($node->children[$ch])) {
                $node->children[$ch] = new TrieNode();
            }
            $node = $node->children[$ch];
        }

        // Update terminal node data
        $node->isEnd = true;
        $node->frequency += $frequency;
        $node->word = $word;
    }

    /**
     * Returns up to $limit suggestions for the given prefix, sorted by
     * descending frequency and then lexicographically.
     *
     * @param string $prefix Prefix to search (case‑insensitive)
     * @param int    $limit  Maximum number of suggestions (0 = no limit)
     *
     * @return Suggestion[] Array of Suggestion objects
     */
    public function autocomplete(string $prefix, int $limit = 0): array
    {
        $prefix = trim(strtolower($prefix));
        $node = $this->root;
        $len = strlen($prefix);
        for ($i = 0; $i < $len; $i++) {
            $ch = $prefix[$i];
            if (!isset($node->children[$ch])) {
                // Prefix not present
                return [];
            }
            $node = $node->children[$ch];
        }

        $candidates = $this->collect($node);
        // Sort by frequency descending, then word ascending
        usort($candidates, static function (Suggestion $a, Suggestion $b): int {
            if ($a->frequency === $b->frequency) {
                return $a->word <=> $b->word;
            }
            return $b->frequency <=> $a->frequency;
        });

        if ($limit > 0 && $limit < count($candidates)) {
            $candidates = array_slice($candidates, 0, $limit);
        }

        return $candidates;
    }

    /**
     * Depth‑first traversal collecting all words beneath $node.
     *
     * @param TrieNode $node Starting node
     *
     * @return Suggestion[] List of suggestions
     */
    private function collect(TrieNode $node): array
    {
        $result = [];

        $stack = [$node];
        while (!empty($stack)) {
            $current = array_pop($stack);
            if ($current->isEnd) {
                $result[] = new Suggestion($current->word, $current->frequency);
            }
            foreach ($current->children as $child) {
                $stack[] = $child;
            }
        }

        return $result;
    }
}
