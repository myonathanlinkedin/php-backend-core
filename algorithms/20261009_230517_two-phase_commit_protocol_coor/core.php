<?php

class Participant
{
    private string $id;
    private string $state; // 'INIT', 'PREPARED', 'COMMITTED', 'ABORTED', 'FAILED'

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->state = 'INIT';
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Simulate the prepare phase.
     * Return true to vote commit, false to vote abort.
     * May throw exception to simulate failure.
     */
    public function prepare(): bool
    {
        if ($this->state !== 'INIT') {
            throw new RuntimeException("Participant {$this->id} in invalid state {$this->state} for prepare");
        }
        // Default behavior: commit vote
        $this->state = 'PREPARED';
        return true;
    }

    public function commit(): void
    {
        if ($this->state !== 'PREPARED') {
            throw new RuntimeException("Participant {$this->id} in invalid state {$this->state} for commit");
        }
        $this->state = 'COMMITTED';
    }

    public function abort(): void
    {
        if ($this->state === 'COMMITTED') {
            throw new RuntimeException("Participant {$this->id} already committed, cannot abort");
        }
        $this->state = 'ABORTED';
    }
}

class Coordinator
{
    private string $transactionId;
    /** @var Participant[] */
    private array $participants;
    private string $state; // 'INIT', 'PREPARING', 'COMMITTING', 'ABORTING', 'DONE'

    public function __construct(string $transactionId, array $participants)
    {
        $this->transactionId = $transactionId;
        $this->participants = $participants;
        $this->state = 'INIT';
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function start(): void
    {
        if ($this->state !== 'INIT') {
            // Already started; ignore
            return;
        }
        $this->state = 'PREPARING';
        $allPrepared = $this->preparePhase();
        if ($allPrepared) {
            $this->commitPhase();
        } else {
            $this->abortPhase();
        }
        $this->state = 'DONE';
    }

    private function preparePhase(): bool
    {
        foreach ($this->participants as $participant) {
            try {
                $vote = $participant->prepare();
                if (!$vote) {
                    // Participant voted abort
                    return false;
                }
            } catch (Throwable $e) {
                // Failure during prepare
                return false;
            }
        }
        return true;
    }

    private function commitPhase(): void
    {
        $this->state = 'COMMITTING';
        foreach ($this->participants as $participant) {
            try {
                $participant->commit();
            } catch (Throwable $e) {
                // In real 2PC, coordinator would log and retry; here we ignore
            }
        }
    }

    private function abortPhase(): void
    {
        $this->state = 'ABORTING';
        foreach ($this->participants as $participant) {
            try {
                $participant->abort();
            } catch (Throwable $e) {
                // Ignore failures during abort
            }
        }
    }
}
