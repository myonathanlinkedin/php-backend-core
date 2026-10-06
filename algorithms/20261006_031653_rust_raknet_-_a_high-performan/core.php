<?php

enum Reliability: string {
    case UNRELIABLE = 'unreliable';
    case RELIABLE   = 'reliable';
}

final class Packet {
    public int $id;
    public string $data;
    public Reliability $reliability;

    public function __construct(int $id, string $data, Reliability $reliability = Reliability::UNRELIABLE) {
        $this->id = $id;
        $this->data = $data;
        $this->reliability = $reliability;
    }
}

interface RaknetTransport {
    public function send(Packet $packet): void;
    public function receive(): ?Packet;
}

final class RaknetServer implements RaknetTransport {
    /** @var array<string, RaknetClient> */
    private array $clients = [];
    /** @var array<Packet> */
    private array $queue = [];

    public function registerClient(string $id, RaknetClient $client): void {
        $this->clients[$id] = $client;
    }

    public function unregisterClient(string $id): void {
        unset($this->clients[$id]);
    }

    public function send(Packet $packet): void {
        $this->queue[] = $packet;
    }

    public function receive(): ?Packet {
        return array_shift($this->queue);
    }

    public function broadcast(Packet $packet): void {
        foreach ($this->clients as $client) {
            $client->receiveFromServer($packet);
        }
    }

    public function processQueue(): void {
        while ($packet = $this->receive()) {
            $this->broadcast($packet);
        }
    }
}

final class RaknetClient implements RaknetTransport {
    private string $id;
    private RaknetServer $server;
    /** @var array<Packet> */
    private array $queue = [];
    /** @var array<int, Packet> */
    private array $unacked = [];

    public function __construct(string $id, RaknetServer $server) {
        $this->id = $id;
        $this->server = $server;
        $this->server->registerClient($id, $this);
    }

    public function send(Packet $packet): void {
        if ($packet->reliability === Reliability::RELIABLE) {
            $this->unacked[$packet->id] = $packet;
        }
        $this->server->send($packet);
    }

    public function receive(): ?Packet {
        return array_shift($this->queue);
    }

    public function receiveFromServer(Packet $packet): void {
        $this->queue[] = $packet;
        if ($packet->reliability === Reliability::RELIABLE) {
            $this->send(new Packet($packet->id, 'ACK', Reliability::UNRELIABLE));
        }
    }

    public function receiveAck(int $id): void {
        unset($this->unacked[$id]);
    }

    public function processAcks(): void {
        foreach ($this->unacked as $id => $packet) {
            $this->send(new Packet($id, 'ACK', Reliability::UNRELIABLE));
        }
    }

    public function shutdown(): void {
        $this->server->unregisterClient($this->id);
    }
}

final class EventLoop {
    private RaknetServer $server;
    /** @var array<RaknetClient> */
    private array $clients = [];

    public function __construct(RaknetServer $server, array $clients) {
        $this->server = $server;
        $this->clients = $clients;
    }

    public function run(int $steps = 10): void {
        for ($i = 0; $i < $steps; $i++) {
            $this->server->processQueue();
            foreach ($this->clients as $client) {
                $client->processAcks();
            }
        }
    }
}
