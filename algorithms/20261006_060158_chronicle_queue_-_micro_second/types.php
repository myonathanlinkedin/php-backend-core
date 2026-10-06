<?php
declare(strict_types=1);

namespace Chronicle;

/**
 * Immutable message stored in the queue.
 */
final class Message
{
    /** @var int Microseconds since Unix epoch */
    public int $timestamp;

    /** @var string Payload */
    public string $payload;

    /**
     * @param string $payload
     * @param int|null $timestamp Microseconds; if null, current time is used.
     */
    public function __construct(string $payload, ?int $timestamp = null)
    {
        $this->payload = $payload;
        $this->timestamp = $timestamp ?? (int) (microtime(true) * 1_000_000);
    }
}

/**
 * Base exception for the Chronicle Queue.
 */
class QueueException extends \Exception
{
}

/**
 * Interface for a writer that can append messages.
 */
interface WriterInterface
{
    public function push(string $payload): void;
}

/**
 * Interface for a consumer that can read messages.
 */
interface ConsumerInterface
{
    /**
     * @return Message|null Returns null when no more messages are available.
     */
    public function pop(): ?Message;
}
