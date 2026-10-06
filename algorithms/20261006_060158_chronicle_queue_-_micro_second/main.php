<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';
require_once __DIR__ . '/engine.php';

use Chronicle\ChronicleQueue;
use Chronicle\Message;
use Chronicle\QueueException;

// Helper to create a clean temporary directory.
function getTempQueueDir(): string
{
    $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'chronicle_demo_' . bin2hex(random_bytes(4));
    if (!mkdir($base, 0700, true) && !is_dir($base)) {
        throw new RuntimeException("Failed to create temp directory: $base");
    }
    return $base;
}

// ---------- Test Suite ----------
$dir = getTempQueueDir();
$queueFile = $dir . DIRECTORY_SEPARATOR . 'queue.dat';

$queue = new ChronicleQueue($queueFile);

// Test 1: Simple push/pop ordering.
$queue->push('msg1');
$queue->push('msg2');
$queue->push('msg3');

$consumerA = $queue->createConsumer('consumerA');

$msg = $consumerA->pop();
assert($msg instanceof Message && $msg->payload === 'msg1', 'First message should be msg1');

$msg = $consumerA->pop();
assert($msg instanceof Message && $msg->payload === 'msg2', 'Second message should be msg2');

$msg = $consumerA->pop();
assert($msg instanceof Message && $msg->payload === 'msg3', 'Third message should be msg3');

$msg = $consumerA->pop();
assert($msg === null, 'Queue should be empty after three reads');

// Test 2: Independent consumers (each sees full stream).
$consumerB = $queue->createConsumer('consumerB');
$msg = $consumerB->pop();
assert($msg instanceof Message && $msg->payload === 'msg1', 'ConsumerB first read should be msg1');
$msg = $consumerB->pop();
assert($msg instanceof Message && $msg->payload === 'msg2', 'ConsumerB second read should be msg2');
$msg = $consumerB->pop();
assert($msg instanceof Message && $msg->payload === 'msg3', 'ConsumerB third read should be msg3');

// Test 3: Persistence across process restarts (simulated by new objects).
unset($queue, $consumerA, $consumerB);
$queue2 = new ChronicleQueue($queueFile);
$consumerC = $queue2->createConsumer('consumerC');
$msg = $consumerC->pop();
assert($msg instanceof Message && $msg->payload === 'msg1', 'After restart, consumerC sees msg1');

// Test 4: Large payload handling.
$largePayload = str_repeat('x', 1024 * 1024); // 1 MiB
$queue2->push($largePayload);
$consumerC2 = $queue2->createConsumer('consumerC2');
$msg = $consumerC2->pop();
assert($msg instanceof Message && $msg->payload === $largePayload, 'Large payload should be retrieved intact');

// Test 5: Edge case – empty payload.
$queue2->push('');
$consumerC3 = $queue2->createConsumer('consumerC3');
$msg = $consumerC3->pop();
assert($msg instanceof Message && $msg->payload === '', 'Empty payload should be handled');

// Clean up temporary files (optional, comment out if inspection needed).
function rrmdir(string $dir): void
{
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}
rrmdir($dir);

echo "All Chronicle Queue tests passed.\n";
