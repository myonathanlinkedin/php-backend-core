<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

use OpenMP\{ParallelLoop, Reduction, MetaLowerer};

// Enable strict assertions.
ini_set('assert.exception', '1');
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

/**
 * Helper to compare generated code with expected code after normalising line endings.
 */
function assertCodeEquals(string $expected, string $actual): void
{
    $norm = static function (string $s): string {
        return trim(str_replace("\r\n", "\n", $s));
    };
    assert($norm($expected) === $norm($actual));
}

/* Unit Test 1: Simple reduction loop */
$loop1 = new ParallelLoop(
    iterator: 'i',
    start: 0,
    end: 100,
    step: 1,
    body: 'sum += a[i];',
    schedule: null,
    reduction: new Reduction(operator: '+', variable: 'sum')
);
$lowerer = new MetaLowerer();
$generated1 = $lowerer->lowerParallelLoop($loop1);
$expected1 = <<<C
#pragma omp parallel for reduction(+:sum)
for (int i = 0; i < 100; i += 1) {
    sum += a[i];
}
C;
assertCodeEquals($expected1, $generated1);

/* Unit Test 2: Loop with static schedule, no reduction */
$loop2 = new ParallelLoop(
    iterator: 'j',
    start: 0,
    end: 50,
    step: 2,
    body: 'process(j);',
    schedule: 'static',
    reduction: null
);
$generated2 = $lowerer->lowerParallelLoop($loop2);
$expected2 = <<<C
#pragma omp parallel for schedule(static)
for (int j = 0; j < 50; j += 2) {
    process(j);
}
C;
assertCodeEquals($expected2, $generated2);

/* Unit Test 3: Multiple loops in a function */
$loop3 = new ParallelLoop(
    iterator: 'k',
    start: 1,
    end: 10,
    step: 1,
    body: 'out[k] = in[k] * factor;',
    schedule: 'dynamic',
    reduction: new Reduction(operator: '*', variable: 'prod')
);
$functionCode = $lowerer->lowerFunction('compute', [$loop1, $loop3]);
$expectedFunction = <<<C
void compute(void) {
    #pragma omp parallel for reduction(+:sum)
    for (int i = 0; i < 100; i += 1) {
        sum += a[i];
    }
    #pragma omp parallel for schedule(dynamic) reduction(*:prod)
    for (int k = 1; k < 10; k += 1) {
        out[k] = in[k] * factor;
    }
}
C;
assertCodeEquals($expectedFunction, $functionCode);

/* Benchmark: generate 1000 simple loops */
$start = microtime(true);
for ($n = 0; $n < 1000; ++$n) {
    $loop = new ParallelLoop(
        iterator: 'i',
        start: 0,
        end: 1000,
        step: 1,
        body: 'arr[i] = i * 2;',
        schedule: null,
        reduction: null
    );
    $lowerer->lowerParallelLoop($loop);
}
$elapsed = microtime(true) - $start;
printf("Generated 1000 loops in %.4f seconds\n", $elapsed);
?>
