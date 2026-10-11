<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

// Configure assertions to be strict.
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
assert_options(ASSERT_BAIL, 1);
assert_options(ASSERT_CALLBACK, function (int $file, int $line, string $code, string $desc = null) {
    $msg = "Assertion failed at {$file}:{$line} - {$code}";
    if ($desc !== null) {
        $msg .= " : {$desc}";
    }
    throw new AssertionError($msg);
});

/**
 * Helper to fetch the latest timestamp from a process.
 */
function latestTimestamp(Process $p): int
{
    $log = $p->getLog();
    $last = end($log);
    return $last instanceof EventLogEntry ? $last->timestamp->value : 0;
}

/* ==========================
   Unit Test Suite
   ========================== */

// Create three processes.
$p1 = new Process(1);
$p2 = new Process(2);
$p3 = new Process(3);

// Initial timestamps should be 1 after construction (creation event).
assert(latestTimestamp($p1) === 1, 'P1 initial timestamp');
assert(latestTimestamp($p2) === 1, 'P2 initial timestamp');
assert(latestTimestamp($p3) === 1, 'P3 initial timestamp');

// P1 sends a message to P2.
$msg1 = $p1->sendMessage($p2, 'Hello from P1');
assert($msg1->timestamp->value === latestTimestamp($p1), 'Message timestamp equals sender clock');

// P2 receives the message.
$p2->receiveMessage($msg1);
assert(latestTimestamp($p2) === max($msg1->timestamp->value, 1) + 1, 'P2 clock after receive');

// P2 performs a local event.
$p2->recordEvent('Local computation');
assert(latestTimestamp($p2) === $msg1->timestamp->value + 2, 'P2 timestamp after local event');

// P2 sends a message to P3.
$msg2 = $p2->sendMessage($p3, 'Forwarded message');
assert($msg2->timestamp->value === latestTimestamp($p2), 'P2 send timestamp');

// P3 receives messages out of order: first a delayed message from P1 (simulate by creating another message).
$msgDelayed = $p1->sendMessage($p3, 'Delayed hello');
$p3->receiveMessage($msgDelayed);
// Verify Lamport rule: P3 timestamp = max(local, remote) + 1
$expectedP3AfterDelayed = max(1, $msgDelayed->timestamp->value) + 1;
assert(latestTimestamp($p3) === $expectedP3AfterDelayed, 'P3 after delayed receive');

// Now P3 receives the earlier message from P2 (which has a smaller timestamp than the delayed one).
$p3->receiveMessage($msg2);
$expectedP3AfterMsg2 = max($expectedP3AfterDelayed, $msg2->timestamp->value) + 1;
assert(latestTimestamp($p3) === $expectedP3AfterMsg2, 'P3 after second receive');

// Verify monotonicity: timestamps never decrease.
assert(latestTimestamp($p1) < latestTimestamp($p2), 'P1 < P2 after interactions');
assert(latestTimestamp($p2) < latestTimestamp($p3), 'P2 < P3 after interactions');

// Edge case: a process sends multiple messages without intervening receives.
$p1->recordEvent('Prepare batch');
$msgBatch1 = $p1->sendMessage($p2, 'Batch 1');
$msgBatch2 = $p1->sendMessage($p2, 'Batch 2');
assert($msgBatch2->timestamp->value === $msgBatch1->timestamp->value + 1, 'Consecutive sends increment');

// P2 receives batch messages in reverse order.
$p2->receiveMessage($msgBatch2);
$tsAfterBatch2 = latestTimestamp($p2);
$p2->receiveMessage($msgBatch1);
$tsAfterBatch1 = latestTimestamp($p2);
assert($tsAfterBatch1 > $tsAfterBatch2, 'Timestamps increase despite reverse receipt');

// All assertions passed if execution reaches this point.
echo "All Lamport logical timestamp tests passed.\n";
