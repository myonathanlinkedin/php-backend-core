<?php
declare(strict_types=1);

require_once __DIR__ . '/types.php';

/**
 * Engine layer – currently thin because all logic resides in the data structure.
 * This file exists to satisfy the three‑file architecture requirement and to
 * expose a convenient factory function.
 */

/**
 * Creates a HyperLogLog instance with the given precision.
 *
 * @param int $precision Desired precision (4‑16). Default 12.
 * @return HyperLogLog
 */
function createHyperLogLog(int $precision = 12): HyperLogLog
{
    $config = new HyperLogLogConfig($precision);
    return new HyperLogLog($config);
}
?>
