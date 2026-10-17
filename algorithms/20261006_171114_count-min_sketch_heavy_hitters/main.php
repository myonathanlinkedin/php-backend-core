<?php
declare(strict_types=1);
require_once 'types.php';

// Helper to generate random string
function randomString(int $length = 8): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $result;
}

// Test parameters
$epsilon = 0.01;
$delta   = 0.01;
$cms     = new CountMinSketch($epsilon, $delta);

// Generate dataset
$totalCount = 0;
$trueCounts = [];
$heavyItems = ['alpha', 'beta', 'gamma'];
foreach ($heavyItems as $item) {
    $cnt = 5000;
    $trueCounts[$item] = $cnt;
    $cms->add($item, $cnt);
    $totalCount += $cnt;
}
for ($i = 0; $i < 10000; $i++) {
    $item = randomString();
    $cnt  = random_int(1, 3);
    $cms->add($item, $cnt);
    $totalCount += $cnt;
    $trueCounts[$item] = ($trueCounts[$item] ?? 0) + $cnt;
}

// Assertions for heavy hitters
$threshold = 4000;
$heavyDetected = $cms->getHeavyHitters(array_keys($trueCounts), $threshold);
sort($heavyDetected);
sort($heavyItems);
assert($heavyDetected === $heavyItems, 'Heavy hitters detection failed.');

// Assertions for frequency estimation error
foreach ($trueCounts as $item => $trueCount) {
    $est = $cms->estimate($item);
    $errorBound = (int) ceil($epsilon * $totalCount);
    assert($est >= $trueCount, "Estimate lower than true count for {$item}.");
    assert($est <= $trueCount + $errorBound, "Estimate exceeds error bound for {$item}.");
}

// Edge case: empty CMS
$emptyCms = new CountMinSketch(0.05, 0.05);
assert($emptyCms->estimate('nonexistent') === 0, 'Empty CMS should return 0.');
assert($emptyCms->getHeavyHitters(['nonexistent'], 1) === [], 'Empty CMS heavy hitters should be empty.');

// Edge case: single item
$singleCms = new CountMinSketch(0.1, 0.1);
$singleCms->add('single', 10);
assert($singleCms->estimate('single') === 10, 'Single item estimate incorrect.');
assert($singleCms->getHeavyHitters(['single'], 5) === ['single'], 'Single item heavy hitter detection failed.');

echo "All tests passed.\n";
?>
