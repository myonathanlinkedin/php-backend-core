<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

// Enable strict assertion handling (throws AssertionError on failure)
assert_options(ASSERT_ACTIVE,   1);
assert_options(ASSERT_EXCEPTION, 1);

/**
 * Helper to create a program from a compact description.
 *
 * @param array $spec Each element is either ['PUSH', int] or a string opcode without operand.
 * @return Instruction[]
 */
function buildProgram(array $spec): array
{
    $program = [];
    foreach ($spec as $item) {
        if (is_array($item)) {
            [$op, $operand] = $item;
            $program[] = new Instruction($op, $operand);
        } else {
            $program[] = new Instruction($item);
        }
    }
    return $program;
}

/* ---------- Unit Tests ---------- */

// Test 1: Empty program yields no output and empty stack.
$vm = new VirtualMachine();
$vm->load([]);
$output = $vm->run();
assert($output === [], 'Empty program should produce empty output.');
assert($vm->getStackSnapshot() === [], 'Stack must remain empty after empty program.');

// Test 2: Simple arithmetic 3 4 ADD => 7
$prog = buildProgram([
    [Opcode::PUSH, 3],
    [Opcode::PUSH, 4],
    Opcode::ADD,
    Opcode::PRINT,
]);
$vm->load($prog);
$output = $vm->run();
assert($output === [7], '3 + 4 should output 7.');
assert($vm->getStackSnapshot() === [], 'Stack should be empty after PRINT.');

// Test 3: Complex expression ((3 + 4) * 5) - 2 = 33
$prog = buildProgram([
    [Opcode::PUSH, 3],
    [Opcode::PUSH, 4],
    Opcode::ADD,          // 7
    [Opcode::PUSH, 5],
    Opcode::MUL,          // 35
    [Opcode::PUSH, 2],
    Opcode::SUB,          // 33
    Opcode::PRINT,
]);
$vm->load($prog);
$output = $vm->run();
assert($output === [33], 'Expression ((3+4)*5)-2 should output 33.');

// Test 4: Stack manipulation DUP and SWAP
$prog = buildProgram([
    [Opcode::PUSH, 10],
    Opcode::DUP,          // stack: 10,10
    [Opcode::PUSH, 5],
    Opcode::SWAP,         // stack: 5,10,10
    Opcode::ADD,          // 5+10=15, stack: 15,10
    Opcode::PRINT,        // prints 15
    Opcode::PRINT,        // prints 10
]);
$vm->load($prog);
$output = $vm->run();
assert($output === [15, 10], 'DUP/SWAP test failed.');

// Test 5: Division by zero must raise VMException.
$prog = buildProgram([
    [Opcode::PUSH, 8],
    [Opcode::PUSH, 0],
    Opcode::DIV,
]);
$vm->load($prog);
$exceptionCaught = false;
try {
    $vm->run();
} catch (VMException $e) {
    $exceptionCaught = true;
    assert($e->getMessage() === 'Division by zero.', 'Incorrect exception message for division by zero.');
}
assert($exceptionCaught, 'Division by zero should raise VMException.');

// Test 6: Stack underflow on ADD.
$prog = buildProgram([
    [Opcode::PUSH, 1],
    Opcode::ADD,
]);
$vm->load($prog);
$exceptionCaught = false;
try {
    $vm->run();
} catch (VMException $e) {
    $exceptionCaught = true;
    assert($e->getMessage() === 'Stack underflow on pop.', 'Incorrect message for stack underflow.');
}
assert($exceptionCaught, 'Stack underflow should raise VMException.');

// Test 7: Unknown opcode handling.
$prog = [new Instruction('FOO')];
$vm->load($prog);
$exceptionCaught = false;
try {
    $vm->run();
} catch (VMException $e) {
    $exceptionCaught = true;
    assert(str_contains($e->getMessage(), 'Unknown opcode'), 'Unexpected message for unknown opcode.');
}
assert($exceptionCaught, 'Unknown opcode should raise VMException.');

// ---------- Demo ----------
echo "Demo: Compute ((3 + 4) * 5) and print result.\n";
$demoProgram = buildProgram([
    [Opcode::PUSH, 3],
    [Opcode::PUSH, 4],
    Opcode::ADD,
    [Opcode::PUSH, 5],
    Opcode::MUL,
    Opcode::PRINT,
]);
$vm->load($demoProgram);
$demoOutput = $vm->run();
foreach ($demoOutput as $value) {
    echo "Output: $value\n";
}
?>
