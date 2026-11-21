<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

// Configure assertions to throw exceptions for test failures.
ini_set('assert.exception', '1');
ini_set('assert.active', '1');
ini_set('assert.warning', '0');

function testWinDetection(): void
{
    $board = [
        'X', 'X', 'X',
        null, 'O', null,
        'O', null, null
    ];
    $game = new TicTacToe($board);
    assert($game->isWin(TicTacToe::PLAYER_X) === true);
    assert($game->isWin(TicTacToe::PLAYER_O) === false);
}

function testDrawDetection(): void
{
    $board = [
        'X', 'O', 'X',
        'X', 'O', 'O',
        'O', 'X', 'X'
    ];
    $game = new TicTacToe($board);
    assert($game->isDraw() === true);
    assert($game->isWin(TicTacToe::PLAYER_X) === false);
    assert($game->isWin(TicTacToe::PLAYER_O) === false);
}

function testBestMoveWin(): void
{
    // X can win by placing at index 2.
    $board = [
        'X', 'X', null,
        'O', 'O', null,
        null, null, null
    ];
    $game = new TicTacToe($board);
    $move = $game->bestMove(TicTacToe::PLAYER_X);
    assert($move === 2);
}

function testBestMoveBlock(): void
{
    // O threatens to win at index 8; X must block.
    $board = [
        'O', 'O', null,
        'X', 'X', null,
        null, null, null
    ];
    $game = new TicTacToe($board);
    $move = $game->bestMove(TicTacToe::PLAYER_X);
    assert($move === 2);
}

function testInitialBestMove(): void
{
    $game = new TicTacToe();
    $move = $game->bestMove(TicTacToe::PLAYER_X);
    // Center (4) is optimal for the first move.
    assert($move === 4);
}

function testMinimaxDrawFromStart(): void
{
    $game = new TicTacToe();
    $score = $game->minimax(TicTacToe::PLAYER_X);
    assert($score === 0);
}

function runAllTests(): void
{
    testWinDetection();
    testDrawDetection();
    testBestMoveWin();
    testBestMoveBlock();
    testInitialBestMove();
    testMinimaxDrawFromStart();
    echo "All tests passed.\n";
}

// Simple benchmark: run bestMove 1000 times from empty board.
function benchmark(): void
{
    $iterations = 1000;
    $start = microtime(true);
    for ($i = 0; $i < $iterations; ++$i) {
        $game = new TicTacToe();
        $game->bestMove(TicTacToe::PLAYER_X);
    }
    $elapsed = microtime(true) - $start;
    printf("Benchmark: %d iterations in %.4f seconds (%.2f ms per run)\n",
        $iterations, $elapsed, ($elapsed / $iterations) * 1000);
}

// Entry point
runAllTests();
benchmark();
