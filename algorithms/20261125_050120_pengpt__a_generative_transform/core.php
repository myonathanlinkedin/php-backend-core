<?php
declare(strict_types=1);

/**
 * Immutable 2‑D point.
 */
final class Point
{
    public float $x;
    public float $y;

    public function __construct(float $x, float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function equals(Point $other, float $epsilon = 1e-9): bool
    {
        return (abs($this->x - $other->x) <= $epsilon) && (abs($this->y - $other->y) <= $epsilon);
    }
}

/**
 * A stroke is an ordered list of points.
 */
final class Stroke
{
    /** @var Point[] */
    private array $points = [];

    /**
     * @param Point[] $points
     */
    public function __construct(array $points = [])
    {
        foreach ($points as $p) {
            if (!($p instanceof Point)) {
                throw new InvalidArgumentException('All elements must be Point instances.');
            }
        }
        $this->points = $points;
    }

    /**
     * @return Point[]
     */
    public function getPoints(): array
    {
        return $this->points;
    }

    public function addPoint(Point $p): void
    {
        $this->points[] = $p;
    }
}

/**
 * Turtle graphics interpreter for a tiny L‑system.
 *
 * Commands:
 *   F – move forward by step, drawing a line.
 *   + – turn right by angle.
 *   - – turn left by angle.
 *   [ – push current state.
 *   ] – pop state.
 *
 * Any other character is ignored.
 */
final class Turtle
{
    private float $x = 0.0;
    private float $y = 0.0;
    private float $angleDeg = 0.0; // 0° points to the right (positive X)
    private float $step;
    private float $angleIncDeg;
    /** @var array<int, array{float,float,float}> */
    private array $stack = [];

    /** @var Point[] */
    private array $trace = [];

    public function __construct(float $step = 1.0, float $angleIncDeg = 90.0)
    {
        $this->step = $step;
        $this->angleIncDeg = $angleIncDeg;
        $this->trace[] = new Point($this->x, $this->y);
    }

    public function forward(): void
    {
        $rad = deg2rad($this->angleDeg);
        $this->x += $this->step * cos($rad);
        $this->y += $this->step * sin($rad);
        $this->trace[] = new Point($this->x, $this->y);
    }

    public function turnRight(): void
    {
        $this->angleDeg -= $this->angleIncDeg;
    }

    public function turnLeft(): void
    {
        $this->angleDeg += $this->angleIncDeg;
    }

    public function pushState(): void
    {
        $this->stack[] = [$this->x, $this->y, $this->angleDeg];
    }

    public function popState(): void
    {
        if (empty($this->stack)) {
            return;
        }
        [$this->x, $this->y, $this->angleDeg] = array_pop($this->stack);
        $this->trace[] = new Point($this->x, $this->y);
    }

    /**
     * Interpret a command string.
     */
    public function interpret(string $commands): void
    {
        $len = strlen($commands);
        for ($i = 0; $i < $len; ++$i) {
            $c = $commands[$i];
            switch ($c) {
                case 'F':
                    $this->forward();
                    break;
                case '+':
                    $this->turnRight();
                    break;
                case '-':
                    $this->turnLeft();
                    break;
                case '[':
                    $this->pushState();
                    break;
                case ']':
                    $this->popState();
                    break;
                default:
                    // ignore unknown symbols
                    break;
            }
        }
    }

    /**
     * @return Stroke
     */
    public function getStroke(): Stroke
    {
        return new Stroke($this->trace);
    }
}

/**
 * PenGenerator produces one or more strokes from a seed string.
 *
 * The seed may contain whitespace to separate independent sub‑strokes.
 */
final class PenGenerator
{
    private float $step;
    private float $angleDeg;

    public function __construct(float $step = 1.0, float $angleDeg = 90.0)
    {
        $this->step = $step;
        $this->angleDeg = $angleDeg;
    }

    /**
     * Generate strokes.
     *
     * @param string $seed   Command string, e.g. "F+F-F  F-F+F"
     * @return Stroke[]
     */
    public function generate(string $seed): array
    {
        $strokes = [];
        $parts = preg_split('/\s+/', trim($seed));
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $turtle = new Turtle($this->step, $this->angleDeg);
            $turtle->interpret($part);
            $strokes[] = $turtle->getStroke();
        }
        return $strokes;
    }
}
