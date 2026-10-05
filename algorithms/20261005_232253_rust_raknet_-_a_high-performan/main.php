<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

use Raknet\Reliability;
use Raknet\Simulator;

// Helper for deterministic randomness in tests
mt_srand(12345);

// Test 1: Simple unreliable transmission
$sim = new Simulator();
$sim->send('hello', Reliability::UNRELIABLE);
$sim->transfer();
$result = $sim->receive();
assert(count($result) === 1 && $result[0] === 'hello');

// Test 2: Reliable ordered delivery with out‑of‑order arrival
$sim = new Simulator();
$sim->send('first', Reliability::RELIABLE_ORDERED, 1);
$sim->send('second', Reliability::RELIABLE_ORDERED, 1);
$sim->transfer();
// Manually shuffle incoming to simulate out‑of‑order
$incoming = $sim->incoming;
shuffle($incoming);
$sim->incoming = $incoming;
$result = $sim->receive();
assert($result === ['first', 'second']);

// Test 3: Fragmentation and reassembly
$large = str_repeat('A', 3000);
$sim = new Simulator();
$sim->send($large, Reliability::RELIABLE);
$sim->transfer();
$result = $sim->receive();
assert(count($result) === 1 && $result[0] === $large);

// Test 4: Simulated packet loss with automatic resend
$sim = new Simulator(0.5); // 50% loss
$sim->send('resend-test', Reliability::RELIABLE);
$sim->transfer();
$first = $sim->receive();
if (empty($first)) {
    // No delivery, trigger resend
    $sim->tick();
    $sim->transfer();
    $second = $sim->receive();
    assert(!empty($second) && $second[0] === 'resend-test');
} else {
    assert($first[0] === 'resend-test');
}

// Benchmark: send 10k small reliable packets
$sim = new Simulator();
$start = microtime(true);
for ($i = 0; $i < 10000; $i++) {
    $sim->send((string)$i, Reliability::RELIABLE);
}
$sim->transfer();
$sim->receive();
$elapsed = microtime(true) - $start;
echo "Processed 10k reliable packets in {$elapsed}s\n";
