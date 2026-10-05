<?php
declare(strict_types=1);

namespace OpenMP;

/**
 * Simple expression wrapper.
 */
final class Expression
{
    public function __construct(public readonly string $code) {}
}

/**
 * Represents a reduction clause in OpenMP.
 */
final class Reduction
{
    public function __construct(
        public readonly string $operator,
        public readonly string $variable,
        public readonly string $type = 'int'
    ) {}
}

/**
 * Represents a parallel for-loop with optional schedule and reduction.
 */
final class ParallelLoop
{
    public function __construct(
        public readonly string $iterator,
        public readonly int $start,
        public readonly int $end,
        public readonly int $step,
        public readonly string $body,
        public readonly ?string $schedule = null,
        public readonly ?Reduction $reduction = null
    ) {}

    /**
     * Returns the loop condition as a string.
     */
    public function getCondition(): string
    {
        $cond = $this->iterator . ' < ' . $this->end;
        return $cond;
    }

    /**
     * Returns the loop increment expression.
     */
    public function getIncrement(): string
    {
        return $this->iterator . ' += ' . $this->step;
    }
}

/**
 * Core class that lowers declarative parallel loops into C code with OpenMP pragmas.
 */
final class MetaLowerer
{
    /**
     * Lower a single ParallelLoop into C source code.
     *
     * @param ParallelLoop $loop
     * @return string Generated C code.
     */
    public function lowerParallelLoop(ParallelLoop $loop): string
    {
        $pragma = '#pragma omp parallel for';
        if ($loop->schedule !== null) {
            $pragma .= ' schedule(' . $loop->schedule . ')';
        }
        if ($loop->reduction !== null) {
            $pragma .= ' reduction(' . $loop->reduction->operator . ':' . $loop->reduction->variable . ')';
        }

        $code  = $pragma . PHP_EOL;
        $code .= 'for (int ' . $loop->iterator . ' = ' . $loop->start . '; '
               . $loop->getCondition() . '; '
               . $loop->getIncrement() . ') {' . PHP_EOL;
        $indentedBody = $this->indent($loop->body, 1);
        $code .= $indentedBody . PHP_EOL;
        $code .= '}' . PHP_EOL;
        return $code;
    }

    /**
     * Lower multiple loops into a single C function.
     *
     * @param string $functionName
     * @param ParallelLoop[] $loops
     * @return string
     */
    public function lowerFunction(string $functionName, array $loops): string
    {
        $code = 'void ' . $functionName . '(void) {' . PHP_EOL;
        foreach ($loops as $loop) {
            $code .= $this->indent($this->lowerParallelLoop($loop), 1);
        }
        $code .= '}' . PHP_EOL;
        return $code;
    }

    /**
     * Helper to indent code by $level tabs.
     */
    private function indent(string $code, int $level): string
    {
        $prefix = str_repeat("\t", $level);
        $lines = explode(PHP_EOL, $code);
        foreach ($lines as &$line) {
            if (trim($line) !== '') {
                $line = $prefix . $line;
            }
        }
        return implode(PHP_EOL, $lines);
    }
}
