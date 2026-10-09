<?php
declare(strict_types=1);

namespace Core;

/**
 * Immutable representation of a playing card.
 */
final class Card
{
    public const SUITS = ['♠', '♥', '♦', '♣'];
    public const RANKS = [
        'A', '2', '3', '4', '5', '6', '7',
        '8', '9', '10', 'J', 'Q', 'K'
    ];

    private string $suit;
    private string $rank;

    public function __construct(string $suit, string $rank)
    {
        $this->suit = $suit;
        $this->rank = $rank;
    }

    public function getSuit(): string
    {
        return $this->suit;
    }

    public function getRank(): string
    {
        return $this->rank;
    }

    public function __toString(): string
    {
        return $this->rank . $this->suit;
    }
}

/**
 * Standard 52‑card deck in a fixed order: spades, hearts, diamonds, clubs,
 * each from Ace to King.
 */
final class Deck
{
    /** @var Card[] */
    private array $cards;

    public function __construct()
    {
        $this->cards = [];
        foreach (Card::SUITS as $suit) {
            foreach (Card::RANKS as $rank) {
                $this->cards[] = new Card($suit, $rank);
            }
        }
    }

    /**
     * @return Card[]
     */
    public function getCards(): array
    {
        return $this->cards;
    }
}

/**
 * Generates k‑combinations of a given set without repetition.
 * Provides both indexed access and an iterator.
 */
final class CombinationGenerator
{
    /** @var array<int,mixed> */
    private array $items;
    private int $n;
    private int $k;
    private int $total;

    /**
     * @param array<int,mixed> $items
     */
    public function __construct(array $items, int $k)
    {
        $this->items = $items;
        $this->n = count($items);
        $this->k = $k;
        if ($k < 0 || $k > $this->n) {
            throw new \InvalidArgumentException('Invalid combination size.');
        }
        $this->total = self::binomial($this->n, $k);
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Returns the k‑element combination at zero‑based $index.
     *
     * @return array<int,mixed>
     */
    public function getCombination(int $index): array
    {
        if ($index < 0 || $index >= $this->total) {
            throw new \OutOfRangeException('Combination index out of range.');
        }
        $indices = self::indicesFromIndex($this->n, $this->k, $index);
        $comb = [];
        foreach ($indices as $i) {
            $comb[] = $this->items[$i];
        }
        return $comb;
    }

    /**
     * Lazily yields each combination as an array.
     *
     * @return \Generator<int,array<int,mixed>>
     */
    public function getIterator(): \Generator
    {
        for ($i = 0; $i < $this->total; $i++) {
            yield $i => $this->getCombination($i);
        }
    }

    /**
     * Computes n choose k using an iterative, overflow‑safe method.
     */
    private static function binomial(int $n, int $k): int
    {
        if ($k < 0 || $k > $n) {
            return 0;
        }
        if ($k === 0 || $k === $n) {
            return 1;
        }
        $k = min($k, $n - $k);
        $result = 1;
        for ($i = 1; $i <= $k; $i++) {
            $result = intdiv($result * ($n - $k + $i), $i);
        }
        return $result;
    }

    /**
     * Translates a lexicographic index to the corresponding combination
     * of element positions.
     *
     * @return int[] Sorted ascending indices.
     */
    private static function indicesFromIndex(int $n, int $k, int $index): array
    {
        $comb = [];
        $a = 0;
        for ($i = $k; $i > 0; $i--) {
            $x = $a;
            while (true) {
                $c = self::binomial($n - $x - 1, $i - 1);
                if ($c <= $index) {
                    $index -= $c;
                    $x++;
                } else {
                    $comb[] = $x;
                    $a = $x + 1;
                    break;
                }
            }
        }
        return $comb;
    }
}
