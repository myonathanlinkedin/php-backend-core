<?php

/**
 * 32-bit MurmurHash3 (x86_32) implementation.
 *
 * @param string $data Input data.
 * @param int    $seed Seed value.
 *
 * @return int 32-bit unsigned hash.
 */
function murmurHash3(string $data, int $seed = 0): int
{
    $len = strlen($data);
    $h1  = $seed & 0xffffffff;
    $c1  = 0xcc9e2d51;
    $c2  = 0x1b873593;
    $roundedEnd = ($len & 0xfffffffc);

    for ($i = 0; $i < $roundedEnd; $i += 4) {
        $k1 = (ord($data[$i]) & 0xff)
            | ((ord($data[$i + 1]) & 0xff) << 8)
            | ((ord($data[$i + 2]) & 0xff) << 16)
            | ((ord($data[$i + 3]) & 0xff) << 24);
        $k1 = ($k1 * $c1) & 0xffffffff;
        $k1 = (($k1 << 15) | ($k1 >> 17)) & 0xffffffff;
        $k1 = ($k1 * $c2) & 0xffffffff;

        $h1 ^= $k1;
        $h1 = (($h1 << 13) | ($h1 >> 19)) & 0xffffffff;
        $h1 = ($h1 * 5 + 0xe6546b64) & 0xffffffff;
    }

    $k1 = 0;
    $tail = $len & 0x03;
    if ($tail == 3) {
        $k1 ^= (ord($data[$roundedEnd + 2]) & 0xff) << 16;
    }
    if ($tail >= 2) {
        $k1 ^= (ord($data[$roundedEnd + 1]) & 0xff) << 8;
    }
    if ($tail >= 1) {
        $k1 ^= (ord($data[$roundedEnd]) & 0xff);
        $k1 = ($k1 * $c1) & 0xffffffff;
        $k1 = (($k1 << 15) | ($k1 >> 17)) & 0xffffffff;
        $k1 = ($k1 * $c2) & 0xffffffff;
        $h1 ^= $k1;
    }

    $h1 ^= $len;
    $h1 ^= ($h1 >> 16);
    $h1 = ($h1 * 0x85ebca6b) & 0xffffffff;
    $h1 ^= ($h1 >> 13);
    $h1 = ($h1 * 0xc2b2ae35) & 0xffffffff;
}
