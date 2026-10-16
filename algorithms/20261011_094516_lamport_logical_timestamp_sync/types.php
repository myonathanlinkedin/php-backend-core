<?php
declare(strict_types=1);

/**
 * Represents a unique identifier for a process in the Lamport system.
 */
final class ProcessId
{
    public function __construct(public readonly int $id) {}
}

/**
 * Immutable Lamport timestamp.
 */
final class LamportTimestamp
{
    public function __construct(public readonly int $value) {}

    public function increment(): LamportTimestamp
    {
        return new LamportTimestamp($this->value + 1);
    }

    public function maxWith(int $other): LamportTimestamp
    {
        return new LamportTimestamp(max($this->value, $other));
    }
}

/**
 * Message exchanged between processes.
 */
final class Message
{
    public function __construct(
        public readonly ProcessId $sender,
        public readonly ProcessId $receiver,
        public readonly LamportTimestamp $timestamp,
        public readonly string $payload
    ) {}
}

/**
 * Simple event log entry.
 */
final class EventLogEntry
{
    public function __construct(
        public readonly string $description,
        public readonly LamportTimestamp $timestamp
    ) {}
}
