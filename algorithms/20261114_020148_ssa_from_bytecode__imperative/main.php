<?php

declare(strict_types=1);
require_once 'core.php';

// Helper to count dest definitions
function countDestDefinitions(CFG $cfg): array
{
    $counts = [];
    foreach ($cfg->blocks as $block) {
        foreach ($block->instructions as $inst) {
            if ($inst->dest !== null) {
                $counts[$inst->dest] = ($counts[$inst->dest] ?? 0) + 1;
            }
        }
    }
    return $counts;
}

// Test 1: Linear SSA conversion
$cfg1 = new CFG('B1');
$block1 = new BasicBlock('B1');
$block1->addInstruction(new Instruction('const', 'a', ['1']));
$block1->addInstruction(new Instruction('add', 'b', ['a', '2']));
$block1->addInstruction(new Instruction('mul', 'c', ['b', '3']));
$block1->addInstruction(new Instruction('sub', 'd', ['c', 'a']));
$cfg1->addBlock($block1);

$converter = new SSAConverter();
$ssaCfg1 = $converter->convert($cfg1);

$defs1 = countDestDefinitions($ssaCfg1);
assert(count($defs1) === 4, 'All variables should be defined once');
foreach ($defs1 as $var => $cnt) {
    assert($cnt === 1, "Variable $var defined $cnt times");
}

// Verify that uses refer to correct SSA names
$insts = $ssaCfg1->getBlock('B1')->instructions;
$expected = [
    ['dest' => 'a$1', 'srcs' => ['1']],
    ['dest' => 'b$1', 'srcs' => ['a$1', '2']],
    ['dest' => 'c$1', 'srcs' => ['b$1', '3']],
    ['dest' => 'd$1', 'srcs' => ['c$1', 'a$1']],
];
for ($i = 0; $i < count($insts); $i++) {
    $inst = $insts[$i];
    assert($inst->dest === $expected[$i]['dest'], "Dest mismatch at instruction $i");
    assert($inst->srcs === $expected[$i]['srcs'], "Srcs mismatch at instruction $i");
}

// Test 2: Variable used before definition (treated as input)
$cfg2 = new CFG('B1');
$block2 = new BasicBlock('B1');
$block2->addInstruction(new Instruction('add', 'x', ['y', '1'])); // y is input
$block2->addInstruction(new Instruction('mul', 'z', ['x', '2']));
$cfg2->addBlock($block2);

$ssaCfg2 = $converter->convert($cfg2);
$defs2 = countDestDefinitions($ssaCfg2);
assert(count($defs2) === 2, 'Two variables defined');
assert(isset($defs2['x$1']), 'x$1 should be defined');
assert(isset($defs2['z$1']), 'z$1 should be defined');

// Verify that input y remains unchanged
$insts2 = $ssaCfg2->getBlock('B1')->instructions;
assert($insts2[0]->srcs[0] === 'y$0', 'Input y should be y$0');

// Test 3: Multiple uses of same variable
$cfg3 = new CFG('B1');
$block3 = new BasicBlock('B1');
$block3->addInstruction(new Instruction('const', 'p', ['5']));
$block3->addInstruction(new Instruction('add', 'q', ['p', 'p'])); // uses p twice
$cfg3->addBlock($block3);

$ssaCfg3 = $converter->convert($cfg3);
$defs3 = countDestDefinitions($ssaCfg3);
assert(count($defs3) === 2, 'Two variables defined');
$insts3 = $ssaCfg3->getBlock('B1')->instructions;
assert($insts3[1]->srcs[0] === 'p$1', 'First use of p should be p$1');
assert($insts3[1]->srcs[1] === 'p$1', 'Second use of p should be p$1');

// All tests passed
echo "All SSA conversion tests passed.\n";
?>
