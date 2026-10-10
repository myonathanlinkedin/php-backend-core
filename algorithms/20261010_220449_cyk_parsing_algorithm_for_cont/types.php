<?php
declare(strict_types=1);

/**
 * Represents a production rule in a context‑free grammar in Chomsky Normal Form.
 * The right‑hand side (RHS) is either:
 *   - an array with a single terminal symbol (e.g. ['a'])
 *   - an array with exactly two non‑terminal symbols (e.g. ['A','B'])
 *   - an empty array for the ε‑production (allowed only for the start symbol).
 */
final class Production
{
    /** @var string Left‑hand side non‑terminal */
    public string $left;

    /** @var array<int,string> Right‑hand side symbols */
    public array $right;

    /**
     * @param string $left
     * @param array<int,string> $right
     */
    public function __construct(string $left, array $right)
    {
        $this->left  = $left;
        $this->right = $right;
    }
}

/**
 * Immutable representation of a context‑free grammar in CNF.
 */
final class Grammar
{
    /** @var string Start symbol */
    public string $startSymbol;

    /** @var array<int,Production> List of productions */
    public array $productions;

    /**
     * @param string $startSymbol
     * @param array<int,Production> $productions
     */
    public function __construct(string $startSymbol, array $productions)
    {
        $this->startSymbol = $startSymbol;
        $this->productions = $productions;
    }

    /**
     * Builds a map from RHS key to the set of LHS non‑terminals that produce it.
     *
     * The key is a string obtained by joining RHS symbols with a single space.
     * For ε‑productions the key is the empty string ''.
     *
     * @return array<string,array<int,string>>  RHS‑key => list of LHS symbols
     */
    public function buildRhsIndex(): array
    {
        $index = [];
        foreach ($this->productions as $prod) {
            $key = implode(' ', $prod->right); // '' for epsilon
            if (!isset($index[$key])) {
                $index[$key] = [];
            }
            $index[$key][] = $prod->left;
        }
        return $index;
    }
}
