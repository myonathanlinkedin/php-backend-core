<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

use Nats\Server;

// Enable strict assertions.
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);
assert_options(ASSERT_WARNING, 1);

// Helper to capture messages.
class Collector
{
    /** @var array<int, array{subject:string,payload:string}> */
    public array $messages = [];

    public function handler(string $label): callable
    {
        return function (\Nats\Message $msg) use ($label): void {
            $this->messages[] = [
                'label'   => $label,
                'subject' => $msg->getSubject(),
                'payload' => $msg->getPayload(),
            ];
        };
    }
}

// Instantiate server.
$server = new Server();

// Create collectors for different subscribers.
$collectorA = new Collector();
$collectorB = new Collector();
$collectorC = new Collector();

// Subscribe with exact subject.
$subA = $server->subscribe('foo.bar', $collectorA->handler('A'));

// Subscribe with single‑level wildcard.
$subB = $server->subscribe('foo.*', $collectorB->handler('B'));

// Subscribe with multi‑level wildcard.
$subC = $server->subscribe('foo.>', $collectorC->handler('C'));

// Publish messages.
$server->publish('foo.bar', 'msg1');
$server->publish('foo.baz', 'msg2');
$server->publish('foo.bar.baz', 'msg3');
$server->publish('foo', 'msg4');

// Assertions for exact match subscriber (A).
assert(count($collectorA->messages) === 1);
assert($collectorA->messages[0]['subject'] === 'foo.bar');
assert($collectorA->messages[0]['payload'] === 'msg1');

// Assertions for single‑level wildcard subscriber (B).
assert(count($collectorB->messages) === 2);
assert($collectorB->messages[0]['subject'] === 'foo.bar');
assert($collectorB->messages[0]['payload'] === 'msg1');
assert($collectorB->messages[1]['subject'] === 'foo.baz');
assert($collectorB->messages[1]['payload'] === 'msg2');

// Assertions for multi‑level wildcard subscriber (C).
assert(count($collectorC->messages) === 4);
assert($collectorC->messages[0]['subject'] === 'foo.bar');
assert($collectorC->messages[1]['subject'] === 'foo.baz');
assert($collectorC->messages[2]['subject'] === 'foo.bar.baz');
assert($collectorC->messages[3]['subject'] === 'foo');

// Test unsubscribe.
$server->unsubscribe($subB);
$server->publish('foo.qux', 'msg5');

// After unsubscribe, B should not receive new messages.
assert(count($collectorB->messages) === 2); // unchanged

// C should still receive the new message.
assert(count($collectorC->messages) === 5);
assert($collectorC->messages[4]['subject'] === 'foo.qux');
assert($collectorC->messages[4]['payload'] === 'msg5');

// Edge case: pattern with only '>' matches everything.
$collectorAll = new Collector();
$subAll = $server->subscribe('>', $collectorAll->handler('ALL'));
$server->publish('any.subject.here', 'msg6');
assert(count($collectorAll->messages) === 1);
assert($collectorAll->messages[0]['subject'] === 'any.subject.here');
assert($collectorAll->messages[0]['payload'] === 'msg6');

// Edge case: empty subject and pattern.
$collectorEmpty = new Collector();
$subEmpty = $server->subscribe('', $collectorEmpty->handler('EMPTY'));
$server->publish('', 'emptymsg');
assert(count($collectorEmpty->messages) === 1);
assert($collectorEmpty->messages[0]['subject'] === '');
assert($collectorEmpty->messages[0]['payload'] === 'emptymsg');

// Verify subscription count reflects current state.
assert($server->getSubscriptionCount() === 5); // A, C, ALL, EMPTY, plus any still active

// Demo output (optional, not required for assertions)
echo "All assertions passed. Server behavior verified.\n";
