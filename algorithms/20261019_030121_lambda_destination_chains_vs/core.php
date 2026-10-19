<?php
declare(strict_types=1);

interface EventHandler
{
    public function handle(Event $event): bool;
    public function getName(): string;
}

final class Event
{
    public string $id;
    /** @var array<string,mixed> */
    public array $payload;

    /**
     * @param string $id
     * @param array<string,mixed> $payload
     */
    public function __construct(string $id, array $payload = [])
    {
        $this->id = $id;
        $this->payload = $payload;
    }
}

/**
 * Simulates an AWS Lambda with retry semantics.
 */
final class LambdaHandler implements EventHandler
{
    private string $name;
    /** @var callable(Event, int): bool */
    private $callback;
    private int $maxRetries;

    /**
     * @param string $name
     * @param callable(Event, int): bool $callback  // receives event and current attempt (1‑based)
     * @param int $maxRetries
     */
    public function __construct(string $name, callable $callback, int $maxRetries = 3)
    {
        $this->name = $name;
        $this->callback = $callback;
        $this->maxRetries = $maxRetries;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Executes the callback respecting retry limits.
     */
    public function handle(Event $event): bool
    {
        $attempt = 0;
        while ($attempt < $this->maxRetries) {
            $attempt++;
            $result = ($this->callback)($event, $attempt);
            if ($result === true) {
                return true;
            }
        }
        return false;
    }
}

/**
 * Simple in‑memory FIFO queue representing an SQS queue.
 */
final class Queue
{
    private string $name;
    /** @var Event[] */
    private array $messages = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function enqueue(Event $event): void
    {
        $this->messages[] = $event;
    }

    public function dequeue(): ?Event
    {
        return array_shift($this->messages);
    }

    public function size(): int
    {
        return count($this->messages);
    }

    /**
     * @return Event[]
     */
    public function all(): array
    {
        return $this->messages;
    }
}

/**
 * Core router that models Lambda destination chains and SQS fan‑out.
 */
final class EventRouter
{
    /** @var array<string, LambdaHandler> */
    private array $handlers = [];

    /** @var array<string, Queue> */
    private array $queues = [];

    /**
     * adjacency list:
     * source name => list of ['type'=> 'handler'|'queue', 'name'=> string]
     *
     * @var array<string, list<array{type:string,name:string}>>
     */
    private array $edges = [];

    public function addHandler(LambdaHandler $handler): void
    {
        $this->handlers[$handler->getName()] = $handler;
    }

    public function addQueue(Queue $queue): void
    {
        $this->queues[$queue->getName()] = $queue;
    }

    /**
     * Connect a source (handler) to a destination (handler or queue).
     *
     * @param string $source   name of existing handler
     * @param string $dest     name of destination handler or queue
     * @param string $type     'handler' or 'queue'
     */
    public function connect(string $source, string $dest, string $type): void
    {
        if (!isset($this->handlers[$source])) {
            throw new InvalidArgumentException("Source handler '{$source}' not found.");
        }
        if ($type === 'handler' && !isset($this->handlers[$dest])) {
            throw new InvalidArgumentException("Destination handler '{$dest}' not found.");
        }
        if ($type === 'queue' && !isset($this->queues[$dest])) {
            throw new InvalidArgumentException("Destination queue '{$dest}' not found.");
        }
        $this->edges[$source][] = ['type' => $type, 'name' => $dest];
    }

    /**
     * Emit an event into the topology starting at $startHandler.
     */
    public function emit(Event $event, string $startHandler): void
    {
        $this->processNode($startHandler, $event);
    }

    private function processNode(string $node, Event $event): void
    {
        if (!isset($this->handlers[$node])) {
            // No such handler – nothing to do.
            return;
        }

        $handler = $this->handlers[$node];
        $success = $handler->handle($event);

        if (!$success) {
            // In a real system this could go to a dead‑letter queue.
            return;
        }

        foreach ($this->edges[$node] ?? [] as $dest) {
            if ($dest['type'] === 'handler') {
                $this->processNode($dest['name'], $event);
            } elseif ($dest['type'] === 'queue') {
                $this->queues[$dest['name']]->enqueue($event);
            }
        }
    }

    public function getQueue(string $name): ?Queue
    {
        return $this->queues[$name] ?? null;
    }

    public function getHandler(string $name): ?LambdaHandler
    {
        return $this->handlers[$name] ?? null;
    }
}
