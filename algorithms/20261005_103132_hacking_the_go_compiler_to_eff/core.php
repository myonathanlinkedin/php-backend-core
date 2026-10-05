<?php
declare(strict_types=1);

/**
 * Core logic for efficient IPv4 to IPv6 mapping.
 * Implements the standard IPv4-mapped IPv6 address format (::ffff:0:0/96).
 */
final class Ipv4ToIpv6Mapper
{
    /**
     * Maps an IPv4 address string to its IPv4-mapped IPv6 representation.
     *
     * @param string $ipv4 The IPv4 address in dotted-decimal notation.
     * @return string The IPv6 address in compressed notation.
     * @throws InvalidArgumentException If the input is not a valid IPv4 address.
     */
    public static function map(string $ipv4): string
    {
        // Validate and normalize the IPv4 address
        $normalized = self::validateAndNormalize($ipv4);
        
        // Parse into 4 octets
        $octets = explode('.', $normalized);
        
        // Construct the IPv4-mapped IPv6 address
        // Format: ::ffff:a.b.c.d
        // The first 80 bits are zero, next 16 bits are 0xffff, last 32 bits are the IPv4 address
        return '::ffff:' . $normalized;
    }

    /**
     * Validates an IPv4 address and returns it in normalized form.
     *
     * @param string $ipv4 The IPv4 address to validate.
     * @return string The normalized IPv4 address.
     * @throws InvalidArgumentException If the address is invalid.
     */
    private static function validateAndNormalize(string $ipv4): string
    {
        // Trim whitespace
        $ipv4 = trim($ipv4);
        
        // Must contain exactly 3 dots
        $parts = explode('.', $ipv4);
        if (count($parts) !== 4) {
            throw new InvalidArgumentException("Invalid IPv4 address: $ipv4");
        }
        
        $octets = [];
        foreach ($parts as $part) {
            // Each part must be a non-empty string of digits
            if ($part === '' || !ctype_digit($part)) {
                throw new InvalidArgumentException("Invalid IPv4 octet: $part");
            }
            
            // Check for leading zeros (except for "0" itself)
            if (strlen($part) > 1 && $part[0] === '0') {
                throw new InvalidArgumentException("Leading zeros not allowed in IPv4 octet: $part");
            }
            
            $value = (int)$part;
            
            // Must be in range 0-255
            if ($value < 0 || $value > 255) {
                throw new InvalidArgumentException("IPv4 octet out of range: $part");
            }
            
            $octets[] = $value;
        }
        
        return implode('.', $octets);
    }

    /**
     * Maps an IPv4 address to its binary representation as a 16-byte IPv6 address.
     *
     * @param string $ipv4 The IPv4 address in dotted-decimal notation.
     * @return string The 16-byte binary representation of the IPv6 address.
     * @throws InvalidArgumentException If the input is not a valid IPv4 address.
     */
    public static function toBinary(string $ipv4): string
    {
        $normalized = self::validateAndNormalize($ipv4);
        $octets = array_map('intval', explode('.', $normalized));
        
        // Construct 16-byte binary representation
        // Bytes 0-7: 0x00
        // Bytes 8-9: 0xff
        // Bytes 10-11: 0x00
        // Bytes 12-15: IPv4 octets
        $binary = str_repeat("\x00", 8) . "\xff\xff" . str_repeat("\x00", 2) . 
                  chr($octets[0]) . chr($octets[1]) . chr($octets[2]) . chr($octets[3]);
        
        return $binary;
    }

    /**
     * Converts a 16-byte binary IPv6 address to its compressed string representation.
     *
     * @param string $binary The 16-byte binary representation.
     * @return string The compressed IPv6 address string.
     */
    public static function binaryToString(string $binary): string
    {
        if (strlen($binary) !== 16) {
            throw new InvalidArgumentException("Binary representation must be exactly 16 bytes");
        }
        
        // Split into 8 groups of 2 bytes (16 bits each)
        $groups = [];
        for ($i = 0; $i < 8; $i++) {
            $byte1 = ord($binary[$i * 2]);
            $byte2 = ord($binary[$i * 2 + 1]);
            $value = ($byte1 << 8) | $byte2;
            $groups[] = dechex($value);
        }
        
        // Find the longest run of consecutive zero groups
        $bestStart = -1;
        $bestLen = 0;
        $currentStart = -1;
        $currentLen = 0;
        
        for ($i = 0; $i < 8; $i++) {
            if ($groups[$i] === '0') {
                if ($currentStart === -1) {
                    $currentStart = $i;
                    $currentLen = 1;
                } else {
                    $currentLen++;
                }
                
                if ($currentLen > $bestLen) {
                    $bestStart = $currentStart;
                    $bestLen = $currentLen;
                }
            } else {
                $currentStart = -1;
                $currentLen = 0;
            }
        }
        
        // Only use :: if the run is at least 2 groups long
        if ($bestLen < 2) {
            $bestStart = -1;
        }
        
        // Build the compressed string
        $parts = [];
        for ($i = 0; $i < 8; $i++) {
            if ($i === $bestStart) {
                $parts[] = '';
                $i += $bestLen - 1;
            } else {
                $parts[] = $groups[$i];
            }
        }
        
        $result = implode(':', $parts);
        
        // Handle edge cases for leading/trailing ::
        if ($result === ':') {
            $result = '::';
        } elseif (str_starts_with($result, ':')) {
            $result = ':' . $result;
        } elseif (str_ends_with($result, ':')) {
            $result = $result . ':';
        }
        
        return $result;
    }
}
