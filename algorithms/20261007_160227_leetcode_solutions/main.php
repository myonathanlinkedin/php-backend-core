<?php
declare(strict_types=1);

require_once 'core.php';

$solver = new LongestSubstringWithoutRepeatingCharacters();

assert($solver->lengthOfLongestSubstring('abcabcbb') === 3);
assert($solver->lengthOfLongestSubstring('bbbbb') === 1);
assert($solver->lengthOfLongestSubstring('pwwkew') === 3);
assert($solver->lengthOfLongestSubstring('') === 0);
assert($solver->lengthOfLongestSubstring('a') === 1);
assert($solver->lengthOfLongestSubstring('dvdf') === 3);
assert($solver->lengthOfLongestSubstring('anviaj') === 5);

echo "All tests passed.\n";
