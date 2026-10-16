<?php
declare(strict_types=1);

final class SuffixAutomaton
{
    /** @var array<int, array{len:int, link:int, next:array<string,int>, occ:int}> */
    private array $states = [];

    /** @var int */
    private int $last = 0;

    public function __construct(string $s)
    {
        // initial state
        $this->states[] = ['len' => 0, 'link' => -1, 'next' => [], 'occ' => 0];
        $this->last = 0;

        foreach (str_split($s) as $c) {
            $this->extend($c);
        }
        $this->prepareOccurrences();
    }

    private function extend(string $c): void
    {
        $cur = count($this->states);
        $this->states[] = [
            'len'  => $this->states[$this->last]['len'] + 1,
            'link' => 0,
            'next' => [],
            'occ'  => 1,               // each added character creates an end position
        ];

        $p = $this->last;
        while ($p !== -1 && !isset($this->states[$p]['next'][$c])) {
            $this->states[$p]['next'][$c] = $cur;
            $p = $this->states[$p]['link'];
        }

        if ($p === -1) {
            $this->states[$cur]['link'] = 0;
        } else {
            $q = $this->states[$p]['next'][$c];
            if ($this->states[$p]['len'] + 1 === $this->states[$q]['len']) {
                $this->states[$cur]['link'] = $q;
            } else {
                // clone state
                $clone = count($this->states);
                $this->states[] = [
                    'len'  => $this->states[$p]['len'] + 1,
                    'link' => $this->states[$q]['link'],
                    'next' => $this->states[$q]['next'],
                    'occ'  => 0,
                ];

                while ($p !== -1 && $this->states[$p]['next'][$c] === $q) {
                    $this->states[$p]['next'][$c] = $clone;
                    $p = $this->states[$p]['link'];
                }

                $this->states[$q]['link']   = $clone;
                $this->states[$cur]['link'] = $clone;
            }
        }

        $this->last = $cur;
    }

    /** Propagate occurrence counts from longer states to their suffix links. */
    private function prepareOccurrences(): void
    {
        $maxLen = 0;
        foreach ($this->states as $st) {
            if ($st['len'] > $maxLen) {
                $maxLen = $st['len'];
            }
        }

        $bucket = array_fill(0, $maxLen + 1, []);
        foreach (array_keys($this->states) as $i) {
            $bucket[$this->states[$i]['len']][] = $i;
        }

        for ($l = $maxLen; $l >= 0; $l--) {
            foreach ($bucket[$l] as $v) {
                $link = $this->states[$v]['link'];
                if ($link !== -1) {
                    $this->states[$link]['occ'] += $this->states[$v]['occ'];
                }
            }
        }
    }

    /** Returns true iff $sub is a substring of the original string. */
    public function contains(string $sub): bool
    {
        $v = 0;
        foreach (str_split($sub) as $c) {
            if (!isset($this->states[$v]['next'][$c])) {
                return false;
            }
            $v = $this->states[$v]['next'][$c];
        }
        return true;
    }

    /**
     * Returns the number of (possibly overlapping) occurrences of $sub
     * in the original string. Returns 0 if $sub is not a substring.
     */
    public function countOccurrences(string $sub): int
    {
        $v = 0;
        foreach (str_split($sub) as $c) {
            if (!isset($this->states[$v]['next'][$c])) {
                return 0;
            }
            $v = $this->states[$v]['next'][$c];
        }
        return $this->states[$v]['occ'];
    }
}
?>
