<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

// Enable assertions in production‑grade mode.
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_EXCEPTION, 1);

/**
 * Helper to create a set of test customers.
 *
 * @return Customer[]
 */
function buildTestCustomers(): array
{
    return [
        new Customer(1,  2.0,  3.0, 1),
        new Customer(2, -1.0,  4.0, 2),
        new Customer(3,  5.0, -2.0, 1),
        new Customer(4, -3.0, -3.0, 3),
        new Customer(5,  0.0,  5.0, 2),
    ];
}

/**
 * Build a small fleet with heterogeneous speeds.
 *
 * @return Vehicle[]
 */
function buildTestVehicles(): array
{
    return [
        new Vehicle('FastTruck', 5, 60.0), // fast, moderate capacity
        new Vehicle('SlowVan',   4, 30.0), // slower, smaller capacity
    ];
}

/**
 * Run unit‑tests on the VRPApproximator.
 */
function runTests(): void
{
    $depot = new Depot(0.0, 0.0);
    $customers = buildTestCustomers();
    $vehicles = buildTestVehicles();

    $approximator = new VRPApproximator();
    $routes = $approximator->planRoutes($customers, $depot, $vehicles);

    // ---- Assertions ---------------------------------------------------------

    // 1. All routes are instances of Route and keyed by vehicle name.
    foreach ($vehicles as $v) {
        assert(isset($routes[$v->name]), "Route for vehicle {$v->name} must exist.");
        assert($routes[$v->name] instanceof Route, "Route for {$v->name} must be a Route object.");
    }

    // 2. Every customer appears exactly once across all routes.
    $seen = [];
    foreach ($routes as $route) {
        foreach ($route->customers as $c) {
            assert(!isset($seen[$c->id]), "Customer {$c->id} assigned multiple times.");
            $seen[$c->id] = true;
        }
    }
    assert(count($seen) === count($customers), 'All customers must be assigned.');

    // 3. Capacity constraints respected.
    foreach ($routes as $route) {
        $totalDemand = $route->getTotalDemand();
        $capacity = $route->vehicle->capacity;
        assert($totalDemand <= $capacity, "Route for {$route->vehicle->name} exceeds capacity.");
    }

    // 4. Travel time is non‑negative and respects speed.
    foreach ($routes as $route) {
        $time = $route->getTravelTime($depot);
        assert($time >= 0.0, "Travel time for {$route->vehicle->name} must be non‑negative.");
        // Simple sanity: time = distance / speed.
        $expected = $route->getDistance($depot) / $route->vehicle->speed;
        assert(abs($time - $expected) < 1e-9, "Travel time calculation mismatch for {$route->vehicle->name}.");
    }

    // 5. Edge case: zero customers.
    $emptyRoutes = $approximator->planRoutes([], $depot, $vehicles);
    foreach ($emptyRoutes as $r) {
        assert(empty($r->customers), "Routes should be empty when no customers are given.");
        assert($r->getDistance($depot) === 0.0, "Distance should be zero for empty route.");
        assert($r->getTravelTime($depot) === 0.0, "Travel time should be zero for empty route.");
    }

    // ---- Demo output --------------------------------------------------------
    echo "=== VRP Approximation Demo ===\n";
    foreach ($routes as $name => $route) {
        $custIds = array_map(fn (Customer $c) => $c->id, $route->customers);
        $dist = $route->getDistance($depot);
        $time = $route->getTravelTime($depot);
        echo "Vehicle {$name} (speed {$route->vehicle->speed}):\n";
        echo "  Customers: " . (empty($custIds) ? 'none' : implode(', ', $custIds)) . "\n";
        echo "  Total demand: {$route->getTotalDemand()} / capacity {$route->vehicle->capacity}\n";
        echo "  Distance: " . number_format($dist, 2) . "\n";
        echo "  Travel time: " . number_format($time, 2) . "\n\n";
    }
}

// Execute tests.
runTests();

?>
