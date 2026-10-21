<?php

class LineBreaker
{
    private int $maxWidth;
    private array $wordWidths;

    public function __construct(int $maxWidth)
    {
        $this->maxWidth = $maxWidth;
    }

    /**
     * Breaks a sequence of word widths into lines minimizing total badness.
     *
     * @param array $wordWidths Array of integer widths for each word.
     * @return array Array of [startIndex, endIndex] pairs for each line.
     */
    public function breakLines(array $wordWidths): array
    {
        $n = count($wordWidths);
        $this->wordWidths = $wordWidths;
        $inf = PHP_INT_MAX;
        $dp = array_fill(0, $n + 1, $inf);
        $prev = array_fill(0, $n + 1, -1);
        $dp[0] = 0;

        for ($i = 0; $i < $n; $i++) {
            $width = 0;
            for ($j = $i; $j < $n; $j++) {
                $width += $wordWidths[$j];
                if ($j > $i) {
                    $width += 1; // space between words
                }

                if ($width > $this->maxWidth) {
                    $badness = ($width - $this->maxWidth) ** 3;
                } else {
                    $badness = ($this->maxWidth - $width) ** 3;
                }

                if ($dp[$i] + $badness < $dp[$j + 1]) {
                    $dp[$j + 1] = $dp[$i] + $badness;
                    $prev[$j + 1] = $i;
                }
            }
        }

        // Reconstruct line breaks
        $lines = [];
        $pos = $n;
        while ($pos > 0) {
            $start = $prev[$pos];
            assert($start !== -1);
            array_unshift($lines, [$start, $pos - 1]);
            $pos = $start;
        }
        return $lines;
    }
}
