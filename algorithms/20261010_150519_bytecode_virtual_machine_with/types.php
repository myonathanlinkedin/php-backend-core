<?php
declare(strict_types=1);

/**
 * Opcode definitions for the bytecode virtual machine.
 */
final class Opcode
{
    public const PUSH  = 'PUSH';   // Push integer literal onto stack
    public const ADD   = 'ADD';    // Pop two values, push sum
    public const SUB   = 'SUB';    // Pop two values, push difference (a - b)
    public const MUL   = 'MUL';    // Pop two values, push product
    public const DIV   = 'DIV';    // Pop two values, push integer division (a / b)
    public const POP   = 'POP';    // Pop and discard top of stack
    public const DUP   = 'DUP';    // Duplicate top of stack
    public const SWAP  = 'SWAP';   // Swap top two stack elements
    public const PRINT = 'PRINT';  // Pop and output value (collected by VM)
}

/**
 * Immutable representation of a single bytecode instruction.
 */
final class Instruction
{
    /** @var string */
    private string $opcode;

    /** @var int|null */
    private ?int $operand;

    /**
     * @param string $opcode One of the Opcode constants.
     * @param int|null $operand Operand for opcodes that require a literal (e.g., PUSH). Null otherwise.
     */
    public function __construct(string $opcode, ?int $operand = null)
    {
        $this->opcode = $opcode;
        $this->operand = $operand;
    }

    public function getOpcode(): string
    {
        return $this->opcode;
    }

    public function getOperand(): ?int
    {
        return $this->operand;
    }
}

/**
 * Base exception for all VM related errors.
 */
class VMException extends \Exception
{
}
