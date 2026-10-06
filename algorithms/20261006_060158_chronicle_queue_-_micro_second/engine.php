<?php
declare(strict_types=1);

namespace Chronicle;

use SplFileObject;

/**
 * Core Chronicle Queue implementation.
 *
 * Stores records as:
 *   8 bytes - unsigned 64‑bit timestamp (microseconds)
 *   4 bytes - unsigned 32‑bit payload length
 *   N bytes - payload
 *
 * All file I/O is performed with advisory locking to guarantee
 * consistency across multiple writers and readers.
 */
final class ChronicleQueue implements WriterInterface
{
    private string $queuePath;

    /**
     * @param string $queuePath Full path to the queue file.
     */
    public function __construct(string $queuePath)
    {
        $this->queuePath = $queuePath;
        // Ensure the file exists.
        $handle = fopen($this->queuePath, 'c+b');
        if ($handle === false) {
            throw new QueueException("Unable to create or open queue file: {$this->queuePath}");
        }
        fclose($handle);
    }

    /**
     * Append a payload to the queue.
     *
     * @param string $payload
     */
    public function push(string $payload): void
    {
        $handle = fopen($this->queuePath, 'c+b');
        if ($handle === false) {
            throw new QueueException('Failed to open queue file for writing.');
        }

        // Exclusive lock for write.
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new QueueException('Unable to acquire exclusive lock for writing.');
        }

        // Seek to end.
        fseek($handle, 0, SEEK_END);

        $timestamp = (int) (microtime(true) * 1_000_000);
        $len = strlen($payload);

        // Pack timestamp (64‑bit unsigned, machine order) and length (32‑bit big endian).
        $binary = pack('J', $timestamp) . pack('N', $len) . $payload;

        $written = fwrite($handle, $binary);
        if ($written === false || $written !== strlen($binary)) {
            flock($handle, LOCK_UN);
            fclose($handle);
            throw new QueueException('Failed to write complete record to queue.');
        }

        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * Create a consumer bound to a logical consumer ID.
     *
     * Each consumer maintains its own offset file.
     *
     * @param string $consumerId Identifier for the consumer (e.g., group name).
     * @return ConsumerInterface
     */
    public function createConsumer(string $consumerId): ConsumerInterface
    {
        return new Consumer($this->queuePath, $consumerId);
    }
}

/**
 * Consumer implementation that tracks its read offset in a side‑car file.
 */
final class Consumer implements ConsumerInterface
{
    private string $queuePath;
    private string $offsetPath;
    private int $offset;

    /**
     * @param string $queuePath Path to the shared queue file.
     * @param string $consumerId Logical identifier for this consumer.
     */
    public function __construct(string $queuePath, string $consumerId)
    {
        $this->queuePath = $queuePath;
        $dir = dirname($queuePath);
        $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $consumerId);
        $this->offsetPath = $dir . DIRECTORY_SEPARATOR . ".offset_{$safeId}";

        $this->offset = $this->loadOffset();
    }

    /**
     * Load persisted offset; if missing, start at 0.
     */
    private function loadOffset(): int
    {
        if (!file_exists($this->offsetPath)) {
            return 0;
        }
        $data = file_get_contents($this->offsetPath);
        if ($data === false) {
            throw new QueueException('Failed to read offset file.');
        }
        $value = (int) $data;
        return $value >= 0 ? $value : 0;
    }

    /**
     * Persist current offset atomically.
     */
    private function persistOffset(): void
    {
        $tmp = $this->offsetPath . '.tmp';
        if (file_put_contents($tmp, (string) $this->offset, LOCK_EX) === false) {
            throw new QueueException('Failed to write temporary offset file.');
        }
        if (!rename($tmp, $this->offsetPath)) {
            throw new QueueException('Failed to rename temporary offset file.');
        }
    }

    /**
     * @inheritDoc
     */
    public function pop(): ?Message
    {
        $handle = fopen($this->queuePath, 'rb');
        if ($handle === false) {
            throw new QueueException('Unable to open queue file for reading.');
        }

        // Shared lock for reading.
        if (!flock($handle, LOCK_SH)) {
            fclose($handle);
            throw new QueueException('Unable to acquire shared lock for reading.');
        }

        // Seek to current offset.
        if (fseek($handle, $this->offset, SEEK_SET) !== 0) {
            flock($handle, LOCK_UN);
            fclose($handle);
            throw new QueueException('Failed to seek to offset.');
        }

        // Read timestamp (8 bytes).
        $tsData = fread($handle, 8);
        if ($tsData === false || strlen($tsData) < 8) {
            // No more data.
            flock($handle, LOCK_UN);
            fclose($handle);
            return null;
        }
        $timestamp = unpack('J', $tsData)[1];

        // Read length (4 bytes).
        $lenData = fread($handle, 4);
        if ($lenData === false || strlen($lenData) < 4) {
            flock($handle, LOCK_UN);
            fclose($handle);
            throw new QueueException('Corrupted length field.');
        }
        $payloadLen = unpack('N', $lenData)[1];

        // Read payload.
        $payload = '';
        if ($payloadLen > 0) {
            $payload = fread($handle, $payloadLen);
            if ($payload === false || strlen($payload) < $payloadLen) {
                flock($handle, LOCK_UN);
                fclose($handle);
                throw new QueueException('Corrupted payload data.');
            }
        }

        // Update offset to point after this record.
        $this->offset += 8 + 4 + $payloadLen;
        $this->persistOffset();

        flock($handle, LOCK_UN);
        fclose($handle);

        return new Message($payload, $timestamp);
    }
}
