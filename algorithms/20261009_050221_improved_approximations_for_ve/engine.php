<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Core heuristic for VRP with non‑uniform vehicle speeds.
 *
 * The algorithm:
 *   1. Sort customers by polar angle around the depot (simple sweep).
 *   2. Sort vehicles by descending speed (fastest first).
 *   3. Greedily assign customers to the current vehicle while capacity permits.
 *   4. Continue with the next vehicle.
 *
 * This yields a feasible solution (all customers visited, capacity respected)
 * and tends to give faster vehicles longer routes, improving overall makespan.
 */
class VRPApproximator
{
    /**
     * @param Customer[] $customers
     * @param Vehicle[]  $vehicles
     * @return Route[]   Array indexed by vehicle name.
     */
    public function planRoutes(array $customers, Depot $depot, array $vehicles): array
    {
        // Defensive checks.
        foreach ($vehicles as $v) {
            if ($v->capacity <= 0) {
                throw new InvalidArgumentException('Vehicle capacity must be positive.');
            }
            if ($v->speed <= 0.0) {
                throw new InvalidArgumentException('Vehicle speed must be positive.');
            }
        }

        // 1. Sort customers by angle around depot.
        $customersSorted = $customers;
        usort($customersSorted, function (Customer $a, Customer $b) use ($depot): int {
            $angleA = atan2($a->y - $depot->y, $a->x - $depot->x);
            $angleB = atan2($b->y - $depot->y, $b->x - $depot->x);
            return $angleA <=> $angleB;
        });

        // 2. Sort vehicles by descending speed.
        $vehiclesSorted = $vehicles;
        usort($vehiclesSorted, function (Vehicle $a, Vehicle $b): int {
            return $b->speed <=> $a->speed;
        });

        // 3. Greedy assignment.
        $unassigned = $customersSorted;
        $routes = [];

        foreach ($vehiclesSorted as $vehicle) {
            $route = new Route($vehicle);
            $remainingCapacity = $vehicle->capacity;

            // Iterate over a copy because we may unset elements.
            foreach ($unassigned as $idx => $cust) {
                if ($cust->demand <= $remainingCapacity) {
                    $route->addCustomer($cust);
                    $remainingCapacity -= $cust->demand;
                    unset($unassigned[$idx]); // remove assigned customer
                }
                // If capacity exhausted, break early.
                if ($remainingCapacity === 0) {
                    break;
                }
            }

            $routes[$vehicle->name] = $route;
        }

        // If any customers remain unassigned, throw – this indicates insufficient total capacity.
        if (!empty($unassigned)) {
            $ids = array_map(fn (Customer $c) => $c->id, $unassigned);
            throw new RuntimeException('Unassigned customers remain: ' . implode(', ', $ids));
        }

        return $routes;
    }
}
