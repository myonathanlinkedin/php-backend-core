<?php
declare(strict_types=1);

require_once 'types.php';

final class MockTimeProvider implements TimeProvider
{
    private int $currentTime;

    public function __construct(int $startTime = 0)
    {
        $this->currentTime = $startTime;
    }

    public function now(): int
    {
        return $this->currentTime;
    }

    public function advance(int $seconds): void
    {
        $this->currentTime += $seconds;
    }
}

// Test 1: Successful notification processing
$timeProvider = new MockTimeProvider();
$queue = new SimpleQueue();
$rateLimiter = new TokenBucketRateLimiter(5, 1);
$retryPolicy = new ExponentialBackoff(1, 2);
$processor = new NotificationProcessor($queue, $rateLimiter, $retryPolicy, $timeProvider);

$notif1 = new Notification('n1', 'user@example.com', 'payload with success');
$queue->enqueue($notif1);
$processor->processAll();

$processed = $processor->getProcessed();
assert(count($processed) === 1, 'Test 1: One notification should be processed');
assert($processed['n1']->attempts === 1, 'Test 1: Should attempt once');

// Test 2: Retry logic with failure
$timeProvider = new MockTimeProvider();
$queue = new SimpleQueue();
$rateLimiter = new TokenBucketRateLimiter(5, 1);
$retryPolicy = new ExponentialBackoff(1, 2);
$processor = new NotificationProcessor($queue, $rateLimiter, $retryPolicy, $timeProvider);

$notif2 = new Notification('n2', 'user@example.com', 'payload with fail', 3);
$queue->enqueue($notif2);
$processor->processAll();

$processed = $processor->getProcessed();
assert(count($processed) === 0, 'Test 2: Failed notification should not be processed');
assert($notif2->attempts === 3, 'Test 2: Should attempt 3 times');

// Test 3: Rate limiting enforcement
$timeProvider = new MockTimeProvider();
$queue = new SimpleQueue();
$rateLimiter = new TokenBucketRateLimiter(1, 1); // 1 token per second
$retryPolicy = new ExponentialBackoff(1, 2);
$processor = new NotificationProcessor($queue, $rateLimiter, $retryPolicy, $timeProvider);

$notif3a = new Notification('n3a', 'user@example.com', 'payload with success');
$notif3b = new Notification('n3b', 'user@example.com', 'payload with success');
$queue->enqueue($notif3a);
$queue->enqueue($notif3b);
$processor->processAll();

$processed = $processor->getProcessed();
assert(count($processed) === 1, 'Test 3: Only one notification should be processed due to rate limit');
assert(isset($processed['n3a']), 'Test 3: First notification processed');
assert(!isset($processed['n3b']), 'Test 3: Second notification blocked');

// Advance time to allow second notification
$timeProvider->advance(1);
$processor->processAll();
$processed = $processor->getProcessed();
assert(count($processed) === 2, 'Test 3: After time advance, second notification processed');
assert(isset($processed['n3b']), 'Test 3: Second notification processed after rate limit reset');

echo "All tests passed.\n";
?>
