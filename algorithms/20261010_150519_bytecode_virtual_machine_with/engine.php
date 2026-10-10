<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Stack‑based bytecode virtual machine.
 *
 * The VM executes an array of Instruction objects. Execution state consists of:
 *   - a stack of integers (LIFO)
 *   - an output buffer (array of integers) populated by PRINT instructions
 *
 * All operations are performed with strict type safety and deterministic semantics.
 */
final class VirtualMachine
{
    /** @var array<int> Stack of integers */
    private array $stack = [];

    /** @var array<int> Collected output values */
    private array $output = [];

    /** @var array<int, Instruction> Program memory */
    private array $program = [];

    /** @var int Instruction pointer */
    private int $ip = 0;

    /**
     * Load a program into the VM.
     *
     * @param Instruction[] $program Sequence of instructions.
     */
    public function load(array $program): void
    {
        $this->program = $program;
        $this->ip = 0;
        $this->stack = [];
        $this->output = [];
    }

    /**
     * Execute the loaded program.
     *
     * @return int[] Output values produced by PRINT instructions.
     *
     * @throws VMException on illegal operations (e.g., stack underflow, division by zero, unknown opcode).
     */
    public function run(): array
    {
        $programSize = count($this->program);
        while ($this->ip < $programSize) {
            $instr = $this->program[$this->ip];
            $this->execute($instr);
            $this->ip++;
        }
        return $this->output;
    }

    /**
     * Execute a single instruction.
     *
     * @param Instruction $instr
     *
     * @throws VMException
     */
    private function execute(Instruction $instr): void
    {
        switch ($instr->getOpcode()) {
            case Opcode::PUSH:
                $operand = $instr->getOperand();
                if ($operand === null) {
                    throw new VMException('PUSH requires an operand.');
                }
                $this->stackPush($operand);
                break;

            case Opcode::ADD:
                $b = $this->stackPop();
                $a = $this->stackPop();
                $this->stackPush($a + $b);
                break;

            case Opcode::SUB:
                $b = $this->stackPop();
                $a = $this->stackPop();
                $this->stackPush($a - $b);
                break;

            case Opcode::MUL:
                $b = $this->stackPop();
                $a = $this->stackPop();
                $this->stackPush($a * $b);
                break;

            case Opcode::DIV:
                $b = $this->stackPop();
                $a = $this->stackPop();
                if ($b === 0) {
                    throw new VMException('Division by zero.');
                }
                // Integer division truncates toward zero.
                $this->stackPush(intdiv($a, $b));
                break;

            case Opcode::POP:
                $this->stackPop(); // discard
                break;

            case Opcode::DUP:
                $value = $this->stackPeek();
                $this->stackPush($value);
                break;

            case Opcode::SWAP:
                $b = $this->stackPop();
                $a = $this->stackPop();
                $this->stackPush($b);
                $this->stackPush($a);
                break;

            case Opcode::PRINT:
                $value = $this->stackPop();
                $this->output[] = $value;
                break;

            default:
                throw new VMException('Unknown opcode: ' . $instr->getOpcode());
        }
    }

    /** @return int */
    private function stackPop(): int
    {
        if (empty($this->stack)) {
            throw new VMException('Stack underflow on pop.');
        }
        return array_pop($this->stack);
    }

    /** @param int $value */
    private function stackPush(int $value): void
    {
        $this->stack[] = $value;
    }

    /** @return int */
    private function stackPeek(): int
    {
        if (empty($this->stack)) {
            throw new VMException('Stack underflow on peek.');
        }
        return $this->stack[count($this->stack) - 1];
    }

    /**
     * Expose internal stack for testing/debugging (read‑only copy).
     *
     * @return int[]
     */
    public function getStackSnapshot(): array
    {
        return $this->stack;
    }
}
