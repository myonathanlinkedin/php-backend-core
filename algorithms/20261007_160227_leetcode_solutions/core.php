<?php
declare(strict_types=1);

class LongestSubstringWithoutRepeatingCharacters
{
    public function lengthOfLongestSubstring(string $s): int
    {
        $n = strlen($s);
        $maxLen = 0;
        $start = 0;
        $lastIndex = [];

        for ($i = 0; $i < $n; $i++) {
            $ch = $s[$i];
            if (isset($lastIndex[$ch]) && $lastIndex[$ch] >= $start) {
                $start = $lastIndex[$ch] + 1;
            }
            $lastIndex[$ch] = $i;
            $currentLen = $i - $start + 1;
            if ($currentLen > $maxLen) {
                $maxLen = $currentLen;
            }
        }
        return $maxLen;
    }
}
