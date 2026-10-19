<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Enable strict assertions.
assert_options(ASSERT_EXCEPTION, 1);

/**
 * Helper to create a deterministic callback.
 *
 * @param list<bool> $outcomes  Indexed by attempt (1‑based). True = success.
 * @return callable(Event, int): bool
 */
function makeCallback(array $outcomes): callable
{
    return static function (Event $event, int $attempt) use ($outcomes): bool {
        return $outcomes[$attempt - 1] ?? false;
    };
}

/* ---------- Test 1: Simple Chain ---------- */
$router = new EventRouter();

$handlerA = new LambdaHandler('A', makeCallback([true]), 3);
$handlerB = new LambdaHandler('B', makeCallback([true]), 3);
$queueC   = new Queue('C');

$router->addHandler($handlerA);
$router->addHandler($handlerB);
$router->addQueue($queueC);

$router->connect('A', 'B', 'handler');
$router->connect('B', 'C', 'queue');

$event1 = new Event('e1');
$router->emit($event1, 'A');

assert($router->getQueue('C') !== null);
assert($router->getQueue('C')->size() === 1);
assert($router->getQueue('C')->all()[0]->id === 'e1');

/* ---------- Test 2: Fan‑out to Two Queues ---------- */
$router2 = new EventRouter();

$handlerF = new LambdaHandler('F', makeCallback([true]), 3);
$queueX   = new Queue('X');
$queueY   = new Queue('Y');

$router2->addHandler($handlerF);
$router2->addQueue($queueX);
$router2->addQueue($queueY);
$router2->connect('F', 'X', 'queue');
$router2->connect('F', 'Y', 'queue');

$event2 = new Event('e2');
$router2->emit($event2, 'F');

assert($router2->getQueue('X')->size() === 1);
assert($router2->getQueue('Y')->size() === 1);
assert($router2->getQueue('X')->all()[0]->id === 'e2');
assert($router2->getQueue('Y')->all()[0]->id === 'e2');

/* ---------- Test 3: Retry Success Within Limits ---------- */
$router3 = new EventRouter();

$handlerR = new LambdaHandler('R', makeCallback([false, false, true]), 5);
$queueR   = new Queue('RQ');

$router3->addHandler($handlerR);
$router3->addQueue($queueR);
$router3->connect('R', 'RQ', 'queue');

$event3 = new Event('e3');
$router3->emit($event3, 'R');

assert($router3->getQueue('RQ')->size() === 1);
assert($router3->getQueue('RQ')->all()[0]->id === 'e3');

/* ---------- Test 4: Exceed Max Retries ---------- */
$router4 = new EventRouter();

$handlerFail = new LambdaHandler('Fail', makeCallback([false, false]), 2);
$queueFail   = new Queue('FailQ');

$router4->addHandler($handlerFail);
$router4->addQueue($queueFail);
$router4->connect('Fail', 'FailQ', 'queue');

$event4 = new Event('e4');
$router4->emit($event4, 'Fail');

assert($router4->getQueue('FailQ')->size() === 0);

/* ---------- Test 5: Mixed Chain & Fan‑out ---------- */
$router5 = new EventRouter();

$handler1 = new LambdaHandler('H1', makeCallback([true]), 3);
$handler2 = new LambdaHandler('H2', makeCallback([true]), 3);
$queueA   = new Queue('A');
$queueB   = new Queue('B');

$router5->addHandler($handler1);
$router5->addHandler($handler2);
$router5->addQueue($queueA);
$router5->addQueue($queueB);

$router5->connect('H1', 'H2', 'handler');
$router5->connect('H1', 'A',  'queue');
$router5->connect('H2', 'B',  'queue');

$event5 = new Event('e5');
$router5->emit($event5, 'H1');

assert($router5->getQueue('A')->size() === 1);
assert($router5->getQueue('B')->size() === 1);
assert($router5->getQueue('A')->all()[0]->id === 'e5');
assert($router5->getQueue('B')->all()[0]->id === 'e5');

/* ---------- All tests passed ---------- */
echo "All unit tests passed.\n";
