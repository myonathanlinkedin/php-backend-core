<?php
declare(strict_types=1);

/**
 * Basic geometric point.
 */
class Point
{
    public float $x;
    public float $y;

    public function __construct(float $x, float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }
}

/**
 * Depot – the origin of all routes.
 */
class Depot extends Point
{
    // No extra fields required.
}

/**
 * Customer with a demand.
 */
class Customer extends Point
{
    public int $id;
    public int $demand;

    public function __construct(int $id, float $x, float $y, int $demand)
    {
        parent::__construct($x, $y);
        $this->id = $id;
        $this->demand = $demand;
    }
}

/**
 * Vehicle definition.
 */
class Vehicle
{
    public int $capacity;
    public float $speed; // distance units per time unit
    public string $name;

    public function __construct(string $name, int $capacity, float $speed)
    {
        $this->name = $name;
        $this->capacity = $capacity;
        $this->speed = $speed;
    }
}

/**
 * Route assigned to a vehicle.
 */
class Route
{
    public Vehicle $vehicle;
    /** @var Customer[] */
    public array $customers = [];

    public function __construct(Vehicle $vehicle)
    {
        $this->vehicle = $vehicle;
    }

    /**
     * Add a customer to the route.
     */
    public function addCustomer(Customer $c): void
    {
        $this->customers[] = $c;
    }

    /**
     * Compute total Euclidean distance of the tour:
     * depot → customers (in order) → depot.
     */
    public function getDistance(Depot $depot): float
    {
        $dist = 0.0;
        $prev = $depot;
        foreach ($this->customers as $c) {
            $dist += euclideanDistance($prev, $c);
            $prev = $c;
        }
        $dist += euclideanDistance($prev, $depot);
        return $dist;
    }

    /**
     * Compute travel time = distance / vehicle speed.
     */
    public function getTravelTime(Depot $depot): float
    {
        $distance = $this->getDistance($depot);
        if ($this->vehicle->speed <= 0.0) {
            throw new RuntimeException('Vehicle speed must be positive.');
        }
        return $distance / $this->vehicle->speed;
    }

    /**
     * Total demand served by this route.
     */
    public function getTotalDemand(): int
    {
        $sum = 0;
        foreach ($this->customers as $c) {
            $sum += $c->demand;
        }
        return $sum;
    }
}

// Helper function declared here for type‑hinting across files.
function euclideanDistance(Point $a, Point $b): float
{
    $dx = $a->x - $b->x;
    $dy = $a->y - $b->y;
    return sqrt($dx * $dx + $dy * $dy);
}
