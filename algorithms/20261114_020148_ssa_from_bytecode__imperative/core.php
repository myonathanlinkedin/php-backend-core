<?php

class Instruction
{
    public string $op;
    public ?string $dest;
    /** @var array<string> */
    public array $srcs;

    public function __construct(string $op, ?string $dest = null, array $srcs = [])
    {
        $this->op   = $op;
        $this->dest = $dest;
        $this->srcs = $srcs;
    }

    public function cloneWith(string $dest, array $srcs): Instruction
    {
        return new Instruction($this->op, $dest, $srcs);
    }

    public function __toString(): string
    {
        $parts = [];
        if ($this->dest !== null) {
            $parts[] = $this->dest . ' =';
        }
        $parts[] = $this->op;
        if (!empty($this->srcs)) {
            $parts[] = implode(', ', $this->srcs);
        }
        return implode(' ', $parts);
    }
}

class BasicBlock
{
    public string $id;
    /** @var Instruction[] */
    public array $instructions = [];
    /** @var string[] */
    public array $successors = [];
    /** @var string[] */
    public array $predecessors = [];

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function addInstruction(Instruction $inst): void
    {
        $this->instructions[] = $inst;
    }

    public function addSuccessor(string $succId): void
    {
        $this->successors[] = $succId;
    }

    public function addPredecessor(string $predId): void
    {
        $this->predecessors[] = $predId;
    }
}

class CFG
{
    /** @var BasicBlock[] */
    public array $blocks = [];
    public string $entry;

    public function __construct(string $entry)
    {
        $this->entry = $entry;
    }

    public function addBlock(BasicBlock $block): void
    {
        $this->blocks[$block->id] = $block;
    }

    public function getBlock(string $id): ?BasicBlock
    {
        return $this->blocks[$id] ?? null;
    }
}

class SSAConverter
{
    /** @var array<string, int> */
    private array $varVersions = [];
    /** @var array<string, array<int>> */
    private array $varStacks = [];

    public function convert(CFG $cfg): CFG
    {
        $newCfg = new CFG($cfg->entry);
        foreach ($cfg->blocks as $block) {
            $newBlock = new BasicBlock($block->id);
            foreach ($block->instructions as $inst) {
                $newInst = $this->renameInstruction($inst);
                $newBlock->addInstruction($newInst);
            }
            $newBlock->successors = $block->successors;
            $newBlock->predecessors = $block->predecessors;
            $newCfg->addBlock($newBlock);
        }
        return $newCfg;
    }

    private function renameInstruction(Instruction $inst): Instruction
    {
        $newSrcs = [];
        foreach ($inst->srcs as $src) {
            $newSrcs[] = $this->currentVersion($src);
        }

        $newDest = null;
        if ($inst->dest !== null) {
            $newDest = $this->newVersion($inst->dest);
        }

        return $inst->cloneWith($newDest, $newSrcs);
    }

    private function currentVersion(string $var): string
    {
        if (!isset($this->varStacks[$var]) || empty($this->varStacks[$var])) {
            return $var . '$0';
        }
        return $this->varStacks[$var][count($this->varStacks[$var]) - 1];
    }

    private function newVersion(string $var): string
    {
        $ver = ($this->varVersions[$var] ?? 0) + 1;
        $this->varVersions[$var] = $ver;
        $newName = $var . '$' . $ver;
        $this->varStacks[$var][] = $newName;
        return $newName;
    }
}
