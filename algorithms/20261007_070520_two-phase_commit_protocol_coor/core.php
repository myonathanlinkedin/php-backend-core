<?php

enum ParticipantState: string {
    case INIT = 'INIT';
    case PREPARED = 'PREPARED';
    case COMMITTED = 'COMMITTED';
    case ABORTED = 'ABORTED';
}

interface ParticipantInterface {
    public function prepare(): bool;
    public function commit(): void;
    public function abort(): void;
    public function getState(): ParticipantState;
}

class Participant implements ParticipantInterface {
    private ParticipantState $state = ParticipantState::INIT;
    private bool $canPrepare;

    public function __construct(bool $canPrepare = true) {
        $this->canPrepare = $canPrepare;
    }

    public function prepare(): bool {
        if ($this->state !== ParticipantState::INIT) {
            return false;
        }
        if ($this->canPrepare) {
            $this->state = ParticipantState::PREPARED;
            return true;
        }
        $this->state = ParticipantState::ABORTED;
        return false;
    }

    public function commit(): void {
        if ($this->state === ParticipantState::PREPARED) {
            $this->state = ParticipantState::COMMITTED;
        }
    }

    public function abort(): void {
        if ($this->state === ParticipantState::PREPARED || $this->state === ParticipantState::INIT) {
            $this->state = ParticipantState::ABORTED;
        }
    }

    public function getState(): ParticipantState {
        return $this->state;
    }
}

class Coordinator {
    /** @var ParticipantInterface[] */
    private array $participants = [];

    public function __construct(array $participants) {
        foreach ($participants as $p) {
            if (!$p instanceof ParticipantInterface) {
                throw new InvalidArgumentException('All participants must implement ParticipantInterface');
            }
        }
        $this->participants = $participants;
    }

    public function prepare(): bool {
        foreach ($this->participants as $p) {
            if (!$p->prepare()) {
                $this->abort();
                return false;
            }
        }
        return true;
    }

    public function commit(): void {
        foreach ($this->participants as $p) {
            $p->commit();
        }
    }

    public function abort(): void {
        foreach ($this->participants as $p) {
            $p->abort();
        }
    }

    public function getStates(): array {
        return array_map(fn(ParticipantInterface $p) => $p->getState(), $this->participants);
    }
}
