<?php
declare(strict_types=1);

class TicTacToe
{
    public const PLAYER_X = 'X';
    public const PLAYER_O = 'O';
    public const EMPTY = null;

    /** @var array<int, string|null> */
    private array $board;

    /**
     * @param array<int, string|null>|null $board
     */
    public function __construct(?array $board = null)
    {
        if ($board === null) {
            $this->board = array_fill(0, 9, self::EMPTY);
        } else {
            $this->board = $board;
        }
    }

    /**
     * @return array<int, string|null>
     */
    public function getBoard(): array
    {
        return $this->board;
    }

    public function isWin(string $player): bool
    {
        $lines = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8], // rows
            [0, 3, 6], [1, 4, 7], [2, 5, 8], // cols
            [0, 4, 8], [2, 4, 6]             // diagonals
        ];
        foreach ($lines as $line) {
            if (
                $this->board[$line[0]] === $player &&
                $this->board[$line[1]] === $player &&
                $this->board[$line[2]] === $player
            ) {
                return true;
            }
        }
        return false;
    }

    public function isDraw(): bool
    {
        return $this->isFull() && !$this->isWin(self::PLAYER_X) && !$this->isWin(self::PLAYER_O);
    }

    private function isFull(): bool
    {
        foreach ($this->board as $cell) {
            if ($cell === self::EMPTY) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array<int>
     */
    public function getAvailableMoves(): array
    {
        $moves = [];
        foreach ($this->board as $i => $cell) {
            if ($cell === self::EMPTY) {
                $moves[] = $i;
            }
        }
        return $moves;
    }

    public function makeMove(int $index, string $player): void
    {
        if ($this->board[$index] !== self::EMPTY) {
            throw new InvalidArgumentException('Cell already occupied');
        }
        $this->board[$index] = $player;
    }

    public function undoMove(int $index): void
    {
        $this->board[$index] = self::EMPTY;
    }

    private function evaluate(): int
    {
        if ($this->isWin(self::PLAYER_X)) {
            return 1;
        }
        if ($this->isWin(self::PLAYER_O)) {
            return -1;
        }
        return 0;
    }

    /**
     * Minimax with alpha-beta pruning.
     *
     * @param string $player Current player to move.
     * @param int $depth   Depth of recursion (used for tie-breaking).
     * @param int $alpha   Alpha value.
     * @param int $beta    Beta value.
     *
     * @return int Evaluation score.
     */
    public function minimax(string $player, int $depth = 0, int $alpha = -PHP_INT_MAX, int $beta = PHP_INT_MAX): int
    {
        $score = $this->evaluate();
        if ($score !== 0 || $this->isFull()) {
            // Terminal node
            return $score;
        }

        $moves = $this->getAvailableMoves();

        if ($player === self::PLAYER_X) {
            $maxEval = -PHP_INT_MAX;
            foreach ($moves as $move) {
                $this->makeMove($move, $player);
                $eval = $this->minimax(self::PLAYER_O, $depth + 1, $alpha, $beta);
                $this->undoMove($move);
                $maxEval = max($maxEval, $eval);
                $alpha = max($alpha, $eval);
                if ($beta <= $alpha) {
                    break; // Beta cut-off
                }
            }
            return $maxEval;
        } else {
            $minEval = PHP_INT_MAX;
            foreach ($moves as $move) {
                $this->makeMove($move, $player);
                $eval = $this->minimax(self::PLAYER_X, $depth + 1, $alpha, $beta);
                $this->undoMove($move);
                $minEval = min($minEval, $eval);
                $beta = min($beta, $eval);
                if ($beta <= $alpha) {
                    break; // Alpha cut-off
                }
            }
            return $minEval;
        }
    }

    /**
     * Returns the index (0‑8) of the optimal move for $player.
     *
     * @param string $player
     *
     * @return int Index of best move; -1 if no moves available.
     */
    public function bestMove(string $player): int
    {
        $bestScore = ($player === self::PLAYER_X) ? -PHP_INT_MAX : PHP_INT_MAX;
        $bestMove = -1;
        foreach ($this->getAvailableMoves() as $move) {
            $this->makeMove($move, $player);
            $score = $this->minimax(
                $player === self::PLAYER_X ? self::PLAYER_O : self::PLAYER_X,
                0,
                -PHP_INT_MAX,
                PHP_INT_MAX
            );
            $this->undoMove($move);

            if ($player === self::PLAYER_X) {
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMove = $move;
                }
            } else {
                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestMove = $move;
                }
            }
        }
        return $bestMove;
    }
}
