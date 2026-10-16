<?php

declare(strict_types=1);
require_once 'core.php';

// Simple assertion helper
function assertEqual($expected, $actual, string $message = ''): void
{
    assert($expected === $actual, $message ?: "Expected {$expected}, got {$actual}");
}

// Test 1: Successful commit
$participants1 = [
    new Participant('P1'),
    new Participant('P2'),
    new Participant('P3'),
];
$coordinator1 = new Coordinator('T1', $participants1);
$coordinator1->start();
assertEqual('DONE', $coordinator1->getState(), 'Coordinator should be DONE after commit');
foreach ($participants1 as $p) {
    assertEqual('COMMITTED', $p->getState(), "Participant {$p->getId()} should be COMMITTED");
}

// Test 2: Abort due to participant vote
class AbortVoteParticipant extends Participant
{
    public function prepare(): bool
    {
        if ($this->getState() !== 'INIT') {
            throw new RuntimeException("Invalid state");
        }
        $this->abort(); // set state to ABORTED
        return false; // vote abort
    }
}
$participants2 = [
    new Participant('P1'),
    new AbortVoteParticipant('P2'),
    new Participant('P3'),
];
$coordinator2 = new Coordinator('T2', $participants2);
$coordinator2->start();
assertEqual('DONE', $coordinator2->getState(), 'Coordinator should be DONE after abort');
foreach ($participants2 as $p) {
    assertEqual('ABORTED', $p->getState(), "Participant {$p->getId()} should be ABORTED");
}

// Test 3: Abort due to prepare failure (exception)
class FailureParticipant extends Participant
{
    public function prepare(): bool
    {
        throw new RuntimeException("Simulated failure");
    }
}
$participants3 = [
    new Participant('P1'),
    new FailureParticipant('P2'),
    new Participant('P3'),
];
$coordinator3 = new Coordinator('T3', $participants3);
$coordinator3->start();
assertEqual('DONE', $coordinator3->getState(), 'Coordinator should be DONE after abort due to failure');
foreach ($participants3 as $p) {
    assertEqual('ABORTED', $p->getState(), "Participant {$p->getId()} should be ABORTED");
}

// Test 4: Repeated start does not change state
$participants4 = [
    new Participant('P1'),
    new Participant('P2'),
];
$coordinator4 = new Coordinator('T4', $participants4);
$coordinator4->start();
$stateAfterFirst = $coordinator4->getState();
$coordinator4->start(); // second call
assertEqual($stateAfterFirst, $coordinator4->getState(), 'Repeated start should not alter state');
foreach ($participants4 as $p) {
    assertEqual('COMMITTED', $p->getState(), "Participant {$p->getId()} should remain COMMITTED");
}

// Test 5: Participant state transitions
$participant = new Participant('P5');
assertEqual('INIT', $participant->getState(), 'Initial state should be INIT');
$participant->prepare();
assertEqual('PREPARED', $participant->getState(), 'After prepare should be PREPARED');
$participant->commit();
assertEqual('COMMITTED', $participant->getState(), 'After commit should be COMMITTED');

// All tests passed
echo "All tests passed.\n";
?>
