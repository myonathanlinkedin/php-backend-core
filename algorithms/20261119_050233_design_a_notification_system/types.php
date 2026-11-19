<?php

interface SenderInterface
{
    public function send(Notification $notification, Recipient $recipient): bool;
}

class Recipient
{
    public string $id;
    public string $address;

    public function __construct(string $id, string $address)
    {
        $this->id = $id;
        $this->address = $address;
    }
}

class Notification
{
    public string $id;
    public string $payload;
    public Recipient $recipient;
    public int $attempts = 0;
    public int $maxAttempts;
    public int $retryDelay; // in seconds

    public function __construct(string $id, string $payload, Recipient $recipient, int $maxAttempts, int $retryDelay)
    {
        $this->id = $id;
        $this->payload = $payload;
        $this->recipient = $recipient;
        $this->maxAttempts = $maxAttempts;
        $this->retryDelay = $retryDelay;
    }
}

class RetryPolicy
{
    private int $maxAttempts;
    private int $initialDelay; // in seconds

    public function __construct(int $maxAttempts, int $initialDelay)
    {
        $this->maxAttempts = $maxAttempts;
        $this->initialDelay = $initialDelay;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getDelay(int $attempt): int
    {
        // Exponential backoff: initialDelay * 2^(attempt-1)
        return $this->initialDelay << ($attempt - 1);
    }
}

class QueueItem
{
    public Notification $notification;
    public int $nextAttemptTime; // epoch seconds

    public function __construct(Notification $notification, int $nextAttemptTime)
    {
        $this->notification = $notification;
        $this->nextAttemptTime = $nextAttemptTime;
    }
}
