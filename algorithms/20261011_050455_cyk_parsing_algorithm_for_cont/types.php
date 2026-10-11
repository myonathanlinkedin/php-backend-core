<?php

class Production
{
    public string $lhs;
    /** @var array<string> */
    public array $rhs;

    public function __construct(string $lhs, array $rhs)
    {
        $this->lhs = $lhs;
        $this->rhs = $rhs;
    }
}

class Grammar
{
    public string $start;
    /** @var array<string> */
    public array $nonterminals;
    /** @var array<string> */
    public array $terminals;
    /** @var Production[] */
    public array $productions = [];

    public function __construct(string $start, array $nonterminals, array $terminals)
    {
        $this->start = $start;
        $this->nonterminals = $nonterminals;
        $this->terminals = $terminals;
    }

    public function addProduction(string $lhs, array $rhs): void
    {
        $this->productions[] = new Production($lhs, $rhs);
    }
}
