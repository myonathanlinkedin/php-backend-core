<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Core Lamport logical clock.
 */
final class LamportClock
{
    private LamportTimestamp $timestamp;

    public function __construct()
    {
        $this->timestamp = new LamportTimestamp(0);
    }

    /**
     * Internal tick for a local event.
     */
    public function tick(): LamportTimestamp
    {
        $this->timestamp = $this->timestamp->increment();
        return $this->timestamp;
    }

    /**
     * Update clock on receipt of a remote timestamp.
     */
    public function updateOnReceive(int $remote): LamportTimestamp
    {
        $max = $this->timestamp->maxWith($remote);
        $this->timestamp = $max->increment();
        return $this->timestamp;
    }

    public function getTimestamp(): LamportTimestamp
    {
        return $this->timestamp;
    }
}

/**
 * Represents a process participating in Lamport synchronization.
 */
final class Process
{
    private readonly ProcessId $id;
    private readonly LamportClock $clock;
    /** @var EventLogEntry[] */
    private array $log = [];

    public function __construct(int $id)
    {
        $this->id = new ProcessId($id);
        $this->clock = new LamportClock();
        $this->recordEvent("Process {$id} created");
    }

    public function getId(): ProcessId
    {
        return $this->id;
    }

    public function getClock(): LamportClock
    {
        return $this->clock;
    }

    /**
     * Record a local event (including internal ticks).
     */
    public function recordEvent(string $description): void
    {
        $ts = $this->clock->tick();
        $this->log[] = new EventLogEntry($description, $ts);
    }

    /**
     * Send a message to another process.
     */
    public function sendMessage(Process $receiver, string $payload): Message
    {
        // Local send event increments clock.
        $sendTs = $this->clock->tick();
        $this->log[] = new EventLogEntry(
            "Sent message to Process {$receiver->getId()->id}",
            $sendTs
        );

        return new Message(
            $this->id,
            $receiver->getId(),
            $sendTs,
            $payload
        );
    }

    /**
     * Receive a message from another process.
     */
    public function receiveMessage(Message $msg): void
    {
        // Update clock based on remote timestamp.
        $recvTs = $this->clock->updateOnReceive($msg->timestamp->value);
        $this->log[] = new EventLogEntry(
            "Received message from Process {$msg->sender->id}",
            $recvTs
        );
    }

    /**
     * Retrieve the event log for inspection.
     *
     * @return EventLogEntry[]
     */
    public function getLog(): array
    {
        return $this->log;
    }
}
