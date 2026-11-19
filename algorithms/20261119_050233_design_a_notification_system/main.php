<?php

class MockSender implements SenderInterface
{
    /** @var array<string, int> */
    private array $failureCounts = [];
    /** @var int */
    private int $failuresBeforeSuccess;

    public function __construct(int $failuresBeforeSuccess = 1)
    {
        $this->failuresBeforeSuccess = $failuresBeforeSuccess;
    }

    public function send(Notification $notification, Recipient $recipient): bool
    {
        $key = $notification->id . '|' . $recipient->id;
        if (!isset($this->failureCounts[$key])) {
            $this->failureCounts[$key] = 0;
        }
        if ($this->failureCounts[$key] < $this->failuresBeforeSuccess) {
            $this->failureCounts[$key] += 1;
            return false;
        }
        return true;
    }
}

// Unit tests and demo
$sender = new MockSender(2); // Fail first 2 attempts
$policy = new RetryPolicy(5, 1); // max 5 attempts, initial delay 1s
$engine = new NotificationEngine($sender, $policy);

// Create recipients
$recipients = [
    new Recipient('r1', 'user1@example.com'),
    new Recipient('r2', 'user2@example.com'),
    new Recipient('r3', 'user3@example.com'),
];

// Create notifications
$notifications = [];
foreach ($recipients as $recipient) {
    $notif = new Notification('n1', 'Hello, ' . $recipient->address, $recipient, $policy->getMaxAttempts(), $policy->initialDelay);
    $engine->enqueue($notif);
    $notifications[] = $notif;
}

// Simulate time loop
$currentTime = 0;
while (true) {
    $engine->process($currentTime);
    // Break if both queues are empty
    $sentLog = $engine->getSentLog();
    if (count($sentLog) === count($recipients)) {
        break;
    }
    $currentTime += 1; // advance time by 1 second
}

// Assertions
$sentLog = $engine->getSentLog();
foreach ($recipients as $recipient) {
    $key = 'n1|' . $recipient->id;
    assert(isset($sentLog[$key]), "Missing log for recipient {$recipient->id}");
    assert($sentLog[$key]['status'] === 'sent', "Notification not sent to {$recipient->id}");
    assert($sentLog[$key]['attempts'] === 3, "Unexpected attempt count for {$recipient->id}");
}

// Edge case: notification with zero recipients
$emptyNotif = new Notification('n2', 'No recipients', new Recipient('dummy', ''), $policy->getMaxAttempts(), $policy->initialDelay);
$engine->enqueue($emptyNotif);
$engine->process(0);
$sentLog = $engine->getSentLog();
assert(isset($sentLog['n2|dummy']), "Log missing for empty notification");
assert($sentLog['n2|dummy']['status'] === 'sent', "Empty notification should be considered sent");
assert($sentLog['n2|dummy']['attempts'] === 1, "Empty notification attempts should be 1");

echo "All tests passed.\n";
?>
