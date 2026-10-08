<?php
declare(strict_types=1);

/**
 * Node used in the optimistic lock-coupled linked list.
 */
final class OLCNode
{
    public int $key;
    public mixed $value;
    public ?OLCNode $next = null;
    public int $version = 0;
    private bool $locked = false;

    public function __construct(int $key, mixed $value, ?OLCNode $next = null)
    {
        $this->key = $key;
        $this->value = $value;
        $this->next = $next;
    }

    /**
     * Acquire exclusive lock on this node.
     */
    public function lock(): void
    {
        // In a real concurrent environment this would block.
        // Here we simply set the flag; if already locked we throw.
        if ($this->locked) {
            throw new RuntimeException('Node already locked');
        }
        $this->locked = true;
    }

    /**
     * Release exclusive lock.
     */
    public function unlock(): void
    {
        $this->locked = false;
    }

    /**
     * Increment version after a successful write.
     */
    public function bumpVersion(): void
    {
        $this->version++;
    }

    /**
     * Check if node is currently locked.
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }
}

/**
 * Optimistic Lock Coupling linked list.
 *
 * Supports get, set, delete with optimistic reads and exclusive writes.
 */
final class OptimisticLinkedList
{
    private OLCNode $head;

    public function __construct()
    {
        // Sentinel head with key PHP_INT_MIN to simplify edge cases.
        $this->head = new OLCNode(PHP_INT_MIN, null);
    }

    /**
     * Optimistic read of a key.
     *
     * @return mixed|null Returns value or null if not found.
     */
    public function get(int $key): mixed
    {
        while (true) {
            $prev = $this->head;
            $curr = $prev->next;

            // Traverse while maintaining order (sorted list for deterministic behavior)
            while ($curr !== null && $curr->key < $key) {
                $prev = $curr;
                $curr = $curr->next;
            }

            // If key not present, validate and return null.
            if ($curr === null || $curr->key !== $key) {
                // Validate that traversed nodes were not modified.
                if ($this->validatePath($prev, $curr)) {
                    return null;
                }
                // Validation failed – retry.
                continue;
            }

            // Record version before reading.
            $verBefore = $curr->version;
            $value = $curr->value;
            $next = $curr->next;
            $verAfter = $curr->version;

            // If version unchanged and node still linked correctly, succeed.
            if ($verBefore === $verAfter && $prev->next === $curr) {
                return $value;
            }

            // Otherwise retry.
        }
    }

    /**
     * Insert or update a key with a value.
     */
    public function set(int $key, mixed $value): void
    {
        while (true) {
            $prev = $this->head;
            $curr = $prev->next;

            // Find insertion point.
            while ($curr !== null && $curr->key < $key) {
                $prev = $curr;
                $curr = $curr->next;
            }

            // Acquire locks on prev and (if exists) curr.
            $this->acquireLocks([$prev, $curr]);

            try {
                // Re-validate after acquiring locks.
                if ($prev->next !== $curr) {
                    // Structure changed, release and retry.
                    $this->releaseLocks([$prev, $curr]);
                    continue;
                }

                if ($curr !== null && $curr->key === $key) {
                    // Update existing node.
                    $curr->value = $value;
                    $curr->bumpVersion();
                } else {
                    // Insert new node between prev and curr.
                    $newNode = new OLCNode($key, $value, $curr);
                    $prev->next = $newNode;
                    $prev->bumpVersion();
                }

                // Success.
                $this->releaseLocks([$prev, $curr]);
                return;
            } catch (Throwable $e) {
                $this->releaseLocks([$prev, $curr]);
                throw $e;
            }
        }
    }

    /**
     * Delete a key from the list.
     *
     * @return bool True if key was present and removed.
     */
    public function delete(int $key): bool
    {
        while (true) {
            $prev = $this->head;
            $curr = $prev->next;

            while ($curr !== null && $curr->key < $key) {
                $prev = $curr;
                $curr = $curr->next;
            }

            if ($curr === null || $curr->key !== $key) {
                // Not found; validate path then return false.
                if ($this->validatePath($prev, $curr)) {
                    return false;
                }
                continue;
            }

            // Acquire locks on prev and curr.
            $this->acquireLocks([$prev, $curr]);

            try {
                if ($prev->next !== $curr) {
                    $this->releaseLocks([$prev, $curr]);
                    continue;
                }

                // Unlink curr.
                $prev->next = $curr->next;
                $prev->bumpVersion();
                // Mark curr as deleted (optional).
                $curr->bumpVersion();

                $this->releaseLocks([$prev, $curr]);
                return true;
            } catch (Throwable $e) {
                $this->releaseLocks([$prev, $curr]);
                throw $e;
            }
        }
    }

    /**
     * Validate that a traversed path (prev -> curr) has not changed.
     */
    private function validatePath(OLCNode $prev, ?OLCNode $curr): bool
    {
        $verPrev = $prev->version;
        $next = $prev->next;
        $verPrevAfter = $prev->version;

        if ($verPrev !== $verPrevAfter) {
            return false;
        }

        // Ensure the expected next node is still linked.
        return $next === $curr;
    }

    /**
     * Acquire exclusive locks on a list of nodes, ignoring nulls.
     */
    private function acquireLocks(array $nodes): void
    {
        foreach ($nodes as $node) {
            if ($node !== null) {
                $node->lock();
            }
        }
    }

    /**
     * Release exclusive locks on a list of nodes, ignoring nulls.
     */
    private function releaseLocks(array $nodes): void
    {
        foreach ($nodes as $node) {
            if ($node !== null) {
                $node->unlock();
            }
        }
    }

    /**
     * For testing: return array representation of the list (excluding sentinel).
     *
     * @return array<int, mixed>
     */
    public function toArray(): array
    {
        $result = [];
        $curr = $this->head->next;
        while ($curr !== null) {
            $result[$curr->key] = $curr->value;
            $curr = $curr->next;
        }
        return $result;
    }
}
