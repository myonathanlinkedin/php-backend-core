<?php
declare(strict_types=1);
require_once 'core.php';

function test_basic_send_receive(): void {
    $server = new RaknetServer();
    $clientA = new RaknetClient('A', $server);
    $clientB = new RaknetClient('B', $server);

    $packet = new Packet(1, 'Hello', Reliability::UNRELIABLE);
    $clientA->send($packet);

    $loop = new EventLoop($server, [$clientA, $clientB]);
    $loop->run();

    $received = $clientB->receive();
    assert($received !== null);
    assert($received->data === 'Hello');
    assert($received->id === 1);
    assert($received->reliability === Reliability::UNRELIABLE);
}

function test_reliable_send(): void {
    $server = new RaknetServer();
    $clientA = new RaknetClient('A', $server);
    $clientB = new RaknetClient('B', $server);

    $packet = new Packet(2, 'Reliable Data', Reliability::RELIABLE);
    $clientA->send($packet);

    $loop = new EventLoop($server, [$clientA, $clientB]);
    $loop->run(5);

    $received = $clientB->receive();
    assert($received !== null);
    assert($received->data === 'Reliable Data');
    assert($received->id === 2);
    assert($received->reliability === Reliability::RELIABLE);

    $ack = $clientB->receive();
    assert($ack !== null);
    assert($ack->data === 'ACK');
    assert($ack->id === 2);
    assert($ack->reliability === Reliability::UNRELIABLE);
}

function test_ack_handling(): void {
    $server = new RaknetServer();
    $clientA = new RaknetClient('A', $server);
    $clientB = new RaknetClient('B', $server);

    $packet = new Packet(3, 'Data', Reliability::RELIABLE);
    $clientA->send($packet);

    $loop = new EventLoop($server, [$clientA, $clientB]);
    $loop->run(3);

    $ack = $clientA->receive();
    assert($ack !== null);
    assert($ack->data === 'ACK');
    assert($ack->id === 3);
    assert($ack->reliability === Reliability::UNRELIABLE);
}

function run_tests(): void {
    test_basic_send_receive();
    test_reliable_send();
    test_ack_handling();
    echo "All tests passed.\n";
}

run_tests();
