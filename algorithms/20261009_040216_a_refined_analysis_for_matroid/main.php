<?php

require_once 'types.php';
require_once 'engine.php';

function assertIndependent(array $elements, Matroid $matroid): void {
    assert($matroid->isIndependent($elements), 'Selected set is not independent');
}

function assertValueAtLeastHalfOptimal(array $elements, SubmodularFunction $f, array $allElements, Matroid $matroid): void {
    $best = 0.0;
    $n = count($allElements);
    for ($mask = 0; $mask < (1 << $n); $mask++) {
        $subset = [];
        for ($i = 0; $i < $n; $i++) {
            if ($mask & (1 << $i)) {
                $subset[] = $allElements[$i];
            }
        }
        if ($matroid->isIndependent($subset)) {
            $val = $f->value($subset);
            if ($val > $best) {
                $best = $val;
            }
        }
    }
    $selectedVal = $f->value($elements);
    assert($selectedVal >= 0.5 * $best, 'Algorithm value less than half of optimum');
}

function testEmptySet(): void {
    $matroid = new UniformMatroid(0);
    $function = new WeightedCardinalityFunction();
    $algorithm = new MatroidSecretaryAlgorithm($matroid, $function);
    $selected = $algorithm->run([]);
    assert(count($selected) === 0, 'Selected set should be empty');
}

function testRankZero(): void {
    $matroid = new UniformMatroid(0);
    $function = new WeightedCardinalityFunction();
    $elements = [new SimpleElement(1, 10.0)];
    $algorithm = new MatroidSecretaryAlgorithm($matroid, $function);
    $selected = $algorithm->run($elements);
    assert(count($selected) === 0, 'No element should be selected when rank is zero');
}

function testUniformMatroid(): void {
    $matroid = new UniformMatroid(2);
    $function = new WeightedCardinalityFunction();
    $elements = [
        new SimpleElement(1, 5.0),
        new SimpleElement(2, 3.0),
        new SimpleElement(3, 4.0),
    ];
    $algorithm = new MatroidSecretaryAlgorithm($matroid, $function);
    $selected = $algorithm->run($elements);
    assertIndependent($selected, $matroid);
    assert(count($selected) >= 1, 'Algorithm should select at least one element');
}

function testOptimalApproximation(): void {
    $matroid = new UniformMatroid(2);
    $function = new WeightedCardinalityFunction();
    $elements = [
        new SimpleElement(1, 10.0),
        new SimpleElement(2, 9.0),
        new SimpleElement(3, 1.0),
    ];
    $algorithm = new MatroidSecretaryAlgorithm($matroid, $function);
    $selected = $algorithm->run($elements);
    assertIndependent($selected, $matroid);
    assertValueAtLeastHalfOptimal($selected, $function, $elements, $matroid);
}

function main(): void {
    testEmptySet();
    testRankZero();
    testUniformMatroid();
    testOptimalApproximation();
    echo "All tests passed.\n";
}

main();
