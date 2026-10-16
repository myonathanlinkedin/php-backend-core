<?php

interface TimeProvider
{
    public function now(): int;
}

final class Notification
{
    public string $id;
    public string $recipient;
    public string $payload;
    public int $attempts;
    public int $maxAttempts;
    public int $nextAttemptTime;

    public function __construct(
        string $id,
        string $recipient,
        string $payload,
        int $maxAttempts = 3
    ) {
        $this->id = $id;
        $this->recipient = $recipient;
        $this->payload = $payload;
        $this->attempts = 0;
        $this->maxAttempts = $maxAttempts;
        $this->nextAttemptTime = 0;
    }

    public function incrementAttempts(): void
    {
        $this->attempts++;
    }

    public function isReady(TimeProvider $timeProvider): bool
    {
        return $this->attempts === 0 || $timeProvider->now() >= $this->nextAttemptTime;
    }

    public function isExhausted(): bool
    {
        return $this->attempts >= $this->maxAttempts;
    }
}

interface RetryPolicy
{
    public function getDelay(int $attempt): int;
}

final class ExponentialBackoff implements RetryPolicy
{
    private int $baseDelay;
    private int $factor;

    public function __construct(int $baseDelay = 1, int $factor = 2)
    {
        $this->baseDelay = $baseDelay;
        $this->factor = $factor;
    }

    public function getDelay(int $attempt): int
    {
        if ($attempt <= 0) {
            return $this->baseDelay;
        }
        return $this->baseDelay * (int)pow($this->factor, $attempt - 1);
    }
}

interface RateLimiter
{
    public function allow(string $recipient, int $currentTime): bool;
    public function record(string $recipient, int $currentTime): void;
}

final class TokenBucketRateLimiter implements RateLimiter
{
    private int $capacity;
    private int $refillRate; // tokens per second
    /** @var array<string, int> */
    private array $tokens;
    /** @var array<string, int> */
    private array $lastRefill;

    public function __construct(int $capacity = 5, int $refillRate = 1)
    {
        $this->capacity = $capacity;
        $this->refillRate = $refillRate;
        $this->tokens = [];
        $this->lastRefill = [];
    }

    private function refill(string $recipient, int $currentTime): void
    {
        if (!isset($this->tokens[$recipient])) {
            $this->tokens[$recipient] = $this->capacity;
            $this->lastRefill[$recipient] = $currentTime;
            return;
        }
        $elapsed = $currentTime - $this->lastRefill[$recipient];
        if ($elapsed <= 0) {
            return;
        }
        $added = $elapsed * $this->refillRate;
        $this->tokens[$recipient] = min($this->capacity, $this->tokens[$recipient] + $added);
        $this->lastRefill[$recipient] = $currentTime;
    }

    public function allow(string $recipient, int $currentTime): bool
    {
        $this->refill($recipient, $currentTime);
        return $this->tokens[$recipient] > 0;
    }

    public function record(string $recipient, int $currentTime): void
    {
        $this->refill($recipient, $currentTime);
        if ($this->tokens[$recipient] > 0) {
            $this->tokens[$recipient]--;
        }
    }
}

interface Queue
{
    public function enqueue(Notification $notification): void;
    public function dequeue(): ?Notification;
    public function isEmpty(): bool;
}

final class SimpleQueue implements Queue
{
    /** @var array<int, Notification> */
    private array $items = [];

    public function enqueue(Notification $notification): void
    {
        $this->items[] = $notification;
    }

    public function dequeue(): ?Notification
    {
        if (empty($this->items)) {
            return null;
        }
        return array_shift($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }
}

final class NotificationProcessor
{
    private Queue $queue;
    private RateLimiter $rateLimiter;
    private RetryPolicy $retryPolicy;
    private TimeProvider $timeProvider;
    /** @var array<string, Notification> */
    private array $processed = [];

    public function __construct(
        Queue $queue,
        RateLimiter $rateLimiter,
        RetryPolicy $retryPolicy,
        TimeProvider $timeProvider
    ) {
        $this->queue = $queue;
        $this->rateLimiter = $rateLimiter;
        $this->retryPolicy = $retryPolicy;
        $this->timeProvider = $timeProvider;
    }

    public function processAll(): void
    {
        while (!$this->queue->isEmpty()) {
            $notification = $this->queue->dequeue();
            if ($notification === null) {
                continue;
            }
            $this->process($notification);
        }
    }

    private function process(Notification $notification): void
    {
        $currentTime = $this->timeProvider->now();
        if (!$notification->isReady($this->timeProvider)) {
            // Not ready yet, re-enqueue
            $this->queue->enqueue($notification);
            return;
        }

        if (!$this->rateLimiter->allow($notification->recipient, $currentTime)) {
            // Rate limited, re-enqueue for later
            $notification->nextAttemptTime = $currentTime + 1; // retry after 1 second
            $this->queue->enqueue($notification);
            return;
        }

        // Record the attempt
        $this->rateLimiter->record($notification->recipient, $currentTime);
        $notification->incrementAttempts();

        // Simulate processing: success if payload contains 'success', else failure
        if (strpos($notification->payload, 'success') !== false) {
            $this->processed[$notification->id] = $notification;
            return;
        }

        // Failure handling
        if ($notification->isExhausted()) {
            // Drop notification
            return;
        }

        // Schedule retry
        $delay = $this->retryPolicy->getDelay($notification->attempts);
        $notification->nextAttemptTime = $currentTime + $delay;
        $this->queue->enqueue($notification);
    }

    /** @return array<string, Notification> */
    public function getProcessed(): array
    {
        return $this->processed;
    }
}
