<?php
declare(strict_types=1);

namespace Raknet;

enum Reliability: int {
    case UNRELIABLE = 0;
    case UNRELIABLE_SEQUENCED = 1;
    case RELIABLE = 2;
    case RELIABLE_ORDERED = 3;
    case RELIABLE_SEQUENCED = 4;
}

final class Packet {
    public const int MAX_PAYLOAD = 1024;

    public readonly int $id;
    public readonly Reliability $reliability;
    public readonly int $sequenceNumber;
    public readonly int $orderChannel;
    public readonly int $orderIndex;
    public readonly string $payload;
    public readonly bool $isFragment;
    public readonly int $fragmentId;
    public readonly int $fragmentCount;

    public function __construct(
        int $id,
        Reliability $reliability,
        int $sequenceNumber,
        int $orderChannel,
        int $orderIndex,
        string $payload,
        bool $isFragment = false,
        int $fragmentId = 0,
        int $fragmentCount = 0
    ) {
        $this->id = $id;
        $this->reliability = $reliability;
        $this->sequenceNumber = $sequenceNumber;
        $this->orderChannel = $orderChannel;
        $this->orderIndex = $orderIndex;
        $this->payload = $payload;
        $this->isFragment = $isFragment;
        $this->fragmentId = $fragmentId;
        $this->fragmentCount = $fragmentCount;
    }
}

final class Channel {
    private int $nextOrderIndex = 0;
    /** @var array<int, Packet> */
    private array $pending = [];

    public function __construct(public readonly int $channelId) {}

    /**
     * @return string[] Ordered payloads ready for delivery
     */
    public function receive(Packet $packet): array {
        $ready = [];
        if ($packet->orderIndex === $this->nextOrderIndex) {
            $ready[] = $packet->payload;
            $this->nextOrderIndex++;
            while (isset($this->pending[$this->nextOrderIndex])) {
                $ready[] = $this->pending[$this->nextOrderIndex]->payload;
                unset($this->pending[$this->nextOrderIndex]);
                $this->nextOrderIndex++;
            }
        } else {
            $this->pending[$packet->orderIndex] = $packet;
        }
        return $ready;
    }
}

final class Simulator {
    /** @var Packet[] */
    private array $outgoing = [];
    /** @var Packet[] */
    private array $incoming = [];
    /** @var array<int, Packet> */
    private array $unacked = [];
    /** @var array<int, array{int, string}> Fragment buffers */
    private array $fragments = [];

    private int $nextPacketId = 1;
    private int $nextSequence = 0;
    private int $nextFragmentId = 1;
    private float $lossProbability;
    private array $channels = [];

    public function __construct(float $lossProbability = 0.0) {
        $this->lossProbability = $lossProbability;
    }

    private function maybeDrop(): bool {
        return $this->lossProbability > 0.0 && mt_rand() / mt_getrandmax() < $this->lossProbability;
    }

    private function getChannel(int $id): Channel {
        return $this->channels[$id] ??= new Channel($id);
    }

    public function send(string $data, Reliability $rel, int $channel = 0): void {
        $fragments = str_split($data, Packet::MAX_PAYLOAD);
        $fragmentCount = count($fragments);
        $fragmentId = $fragmentCount > 1 ? $this->nextFragmentId++ : 0;
        foreach ($fragments as $i => $chunk) {
            $packet = new Packet(
                $this->nextPacketId++,
                $rel,
                $this->nextSequence++,
                $channel,
                $this->nextSequence, // order index uses sequence for simplicity
                $chunk,
                $fragmentCount > 1,
                $fragmentId,
                $fragmentCount
            );
            $this->outgoing[] = $packet;
            if ($rel->value >= Reliability::RELIABLE->value) {
                $this->unacked[$packet->id] = $packet;
            }
        }
    }

    /**
     * Simulate network transfer: move outgoing to incoming respecting loss.
     */
    public function transfer(): void {
        foreach ($this->outgoing as $packet) {
            if ($this->maybeDrop()) {
                continue; // drop packet
            }
            $this->incoming[] = $packet;
        }
        $this->outgoing = [];
    }

    /**
     * Process incoming packets, handle ACKs, ordering, and reassembly.
     *
     * @return string[] Fully assembled messages ready for application layer.
     */
    public function receive(): array {
        $delivered = [];
        foreach ($this->incoming as $packet) {
            // Auto‑ACK reliable packets
            if ($packet->reliability->value >= Reliability::RELIABLE->value) {
                unset($this->unacked[$packet->id]);
            }

            // Fragment handling
            if ($packet->isFragment) {
                $key = $packet->fragmentId;
                $this->fragments[$key]['total'] = $packet->fragmentCount;
                $this->fragments[$key]['parts'][$packet->fragmentId] = $packet->payload;
                if (count($this->fragments[$key]['parts']) === $packet->fragmentCount) {
                    ksort($this->fragments[$key]['parts']);
                    $assembled = implode('', $this->fragments[$key]['parts']);
                    $packet = new Packet(
                        $packet->id,
                        $packet->reliability,
                        $packet->sequenceNumber,
                        $packet->orderChannel,
                        $packet->orderIndex,
                        $assembled,
                        false,
                        0,
                        0
                    );
                    unset($this->fragments[$key]);
                } else {
                    continue; // wait for more fragments
                }
            }

            // Ordering for reliable ordered / sequenced
            if (in_array($packet->reliability, [Reliability::RELIABLE_ORDERED, Reliability::RELIABLE_SEQUENCED], true)) {
                $channel = $this->getChannel($packet->orderChannel);
                $ready = $channel->receive($packet);
                foreach ($ready as $msg) {
                    $delivered[] = $msg;
                }
            } else {
                $delivered[] = $packet->payload;
            }
        }
        $this->incoming = [];
        return $delivered;
    }

    /**
     * Simulate a tick where unacked reliable packets are resent.
     */
    public function tick(): void {
        foreach ($this->unacked as $packet) {
            // Simple resend without exponential backoff
            $this->outgoing[] = $packet;
        }
    }
}
