<?php
declare(strict_types=1);
require_once 'core.php';

function test_successful_commit(): void {
    $participants = [
        new Participant(true),
        new Participant(true),
        new Participant(true),
    ];
    $coordinator = new Coordinator($participants);
    assert($coordinator->prepare() === true);
    $coordinator->commit();
    foreach ($participants as $p) {
        assert($p->getState() === ParticipantState::COMMITTED);
    }
}

function test_prepare_failure(): void {
    $participants = [
        new Participant(true),
        new Participant(false),
        new Participant(true),
    ];
    $coordinator = new Coordinator($participants);
    assert($coordinator->prepare() === false);
    foreach ($participants as $p) {
        assert($p->getState() === ParticipantState::ABORTED);
    }
}

function test_abort_after_prepare(): void {
    $participants = [
        new Participant(true),
        new Participant(true),
    ];
    $coordinator = new Coordinator($participants);
    assert($coordinator->prepare() === true);
    $coordinator->abort();
    foreach ($participants as $p) {
        assert($p->getState() === ParticipantState::ABORTED);
    }
}

function benchmark(): void {
    $participants = [];
    for ($i = 0; $i < 1000; $i++) {
        $participants[] = new Participant(true);
    }
    $coordinator = new Coordinator($participants);
    $start = microtime(true);
    $coordinator->prepare();
    $coordinator->commit();
    $duration = microtime(true) - $start;
    echo "Benchmark: 1000 participants commit in {$duration}s\n";
}

test_successful_commit();
test_prepare_failure();
test_abort_after_prepare();
benchmark();
