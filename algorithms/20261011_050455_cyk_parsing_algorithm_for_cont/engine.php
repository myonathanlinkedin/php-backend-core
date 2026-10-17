<?php

function cykParse(string $input, Grammar $grammar): bool
{
    $n = strlen($input);
    // Handle epsilon production
    if ($n === 0) {
        foreach ($grammar->productions as $prod) {
            if ($prod->lhs === $grammar->start && count($prod->rhs) === 0) {
                return true;
            }
        }
        return false;
    }

    // Precompute terminal and pair maps
    /** @var array<string, array<string>> */
    $termMap = [];
    /** @var array<string, array<string>> */
    $pairMap = [];
    foreach ($grammar->productions as $prod) {
        $rhs = $prod->rhs;
        if (count($rhs) === 1) {
            $term = $rhs[0];
            $termMap[$term][] = $prod->lhs;
        } elseif (count($rhs) === 2) {
            [$B, $C] = $rhs;
            $pairMap[$B][$C][] = $prod->lhs;
        }
    }

    // Initialize table: $table[i][j] is associative array of nonterminals
    /** @var array<int, array<int, array<string>>> */
    $table = [];
    for ($i = 0; $i < $n; $i++) {
        $table[$i] = [];
        for ($j = 0; $j < $n; $j++) {
            $table[$i][$j] = [];
        }
    }

    // Fill table for substrings of length 1
    for ($i = 0; $i < $n; $i++) {
        $char = $input[$i];
        if (isset($termMap[$char])) {
            foreach ($termMap[$char] as $A) {
                $table[$i][$i][$A] = true;
            }
        }
    }

    // CYK dynamic programming
    for ($len = 2; $len <= $n; $len++) {
        for ($i = 0; $i <= $n - $len; $i++) {
            $j = $i + $len - 1;
            for ($k = $i; $k < $j; $k++) {
                $leftSet = $table[$i][$k];
                $rightSet = $table[$k + 1][$j];
                foreach ($leftSet as $B => $_) {
                    foreach ($rightSet as $C => $_) {
                        if (isset($pairMap[$B][$C])) {
                            foreach ($pairMap[$B][$C] as $A) {
                                $table[$i][$j][$A] = true;
                            }
                        }
                    }
                }
            }
        }
    }

    return isset($table[0][$n - 1][$grammar->start]);
}
