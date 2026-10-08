<?php
declare(strict_types=1);

namespace Nats;

/**
 * Simple immutable message representation.
 */
final class Message
{
    private string $subject;
    private string $payload;

    public function __construct(string $subject, string $payload)
    {
        $this->subject = $subject;
        $this->payload = $payload;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }
}

/**
 * Subscription descriptor.
 */
final class Subscription
{
    private int $id;
    private string $subject;
    /** @var callable(Message): void */
    private $handler;

    /**
     * @param callable(Message): void $handler
     */
    public function __construct(int $id, string $subject, callable $handler)
    {
        $this->id = $id;
        $this->subject = $subject;
        $this->handler = $handler;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return callable(Message): void
     */
    public function getHandler(): callable
    {
        return $this->handler;
    }
}
