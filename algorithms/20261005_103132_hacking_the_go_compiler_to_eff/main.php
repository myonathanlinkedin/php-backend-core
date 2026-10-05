<?php
declare(strict_types=1);

require_once __DIR__ . '/core.php';

/**
 * Unit test suite for Ipv4ToIpv6Mapper.
 */
final class Ipv4ToIpv6MapperTest
{
    private static int $passed = 0;
    private static int $failed = 0;

    /**
     * Runs a single test case.
     */
    private static function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected === $actual) {
            self::$passed++;
            echo "✓ PASS: $message\n";
        } else {
            self::$failed++;
            echo "✗ FAIL: $message\n";
            echo "  Expected: " . var_export($expected, true) . "\n";
            echo "  Actual:   " . var_export($actual, true) . "\n";
        }
    }

    /**
     * Runs a test that expects an exception.
     */
    private static function assertThrows(callable $fn, string $exceptionClass, string $message): void
    {
        try {
            $fn();
            self::$failed++;
            echo "✗ FAIL: $message (no exception thrown)\n";
        } catch (Throwable $e) {
            if ($e instanceof $exceptionClass) {
                self::$passed++;
                echo "✓ PASS: $message\n";
            } else {
                self::$failed++;
                echo "✗ FAIL: $message (wrong exception: " . get_class($e) . ")\n";
            }
        }
    }

    /**
     * Runs all tests.
     */
    public static function run(): void
    {
        echo "Running Ipv4ToIpv6Mapper Test Suite\n";
        echo str_repeat('=', 50) . "\n";

        // Test valid mappings
        self::assertSame('::ffff:192.168.1.1', Ipv4ToIpv6Mapper::map('192.168.1.1'), 'Map 192.168.1.1');
        self::assertSame('::ffff:10.0.0.1', Ipv4ToIpv6Mapper::map('10.0.0.1'), 'Map 10.0.0.1');
        self::assertSame('::ffff:172.16.0.1', Ipv4ToIpv6Mapper::map('172.16.0.1'), 'Map 172.16.0.1');
        self::assertSame('::ffff:0.0.0.0', Ipv4ToIpv6Mapper::map('0.0.0.0'), 'Map 0.0.0.0');
        self::assertSame('::ffff:255.255.255.255', Ipv4ToIpv6Mapper::map('255.255.255.255'), 'Map 255.255.255.255');
        self::assertSame('::ffff:8.8.8.8', Ipv4ToIpv6Mapper::map('8.8.8.8'), 'Map 8.8.8.8');
        self::assertSame('::ffff:1.2.3.4', Ipv4ToIpv6Mapper::map('1.2.3.4'), 'Map 1.2.3.4');

        // Test with whitespace
        self::assertSame('::ffff:192.168.1.1', Ipv4ToIpv6Mapper::map('  192.168.1.1  '), 'Map with whitespace');

        // Test invalid inputs
        self::assertThrows(fn() => Ipv4ToIpv6Mapper::map('192.168.1'), InvalidArgumentException::class, 'Reject 3 octets');
        self::assertThrows(fn() => Ipv4ToIpv6Mapper::map('192.168.1.1.1'), InvalidArgumentException::class, 'Reject 5 octets');
        self::assertThrows(fn() => Ipv4ToIpv6Mapper::map('256.0.0.1'), InvalidArgumentException::class, 'Reject octet > 255');
        self::assertThrows(fn() => Ipv4ToIpv6Mapper::map('192.168.01.1'), InvalidArgumentException::class, 'Reject leading zeros');
        self::assertThrows(fn() => Ipv4ToIpv6Mapper::map('192.168.1.1a'), InvalidArgumentException::class, 'Reject non-numeric');
        self::assertThrows(fn() => Ipv4ToIpv6Mapper::map(''), InvalidArgumentException::class, 'Reject empty string');
        self::assertThrows(fn() => Ipv4ToIpv6Mapper::map('192.168.1.'), InvalidArgumentException::class, 'Reject trailing dot');
        self::assertThrows(fn() => Ipv4ToIpv6Mapper::map('.192.168.1.1'), InvalidArgumentException::class, 'Reject leading dot');

        // Test binary conversion
        $binary = Ipv4ToIpv6Mapper::toBinary('192.168.1.1');
        self::assertSame(16, strlen($binary), 'Binary length is 16 bytes');
        self::assertSame("\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\x00\x00\xc0\xa8\x01\x01", $binary, 'Binary representation of 192.168.1.1');

        $binary2 = Ipv4ToIpv6Mapper::toBinary('0.0.0.0');
        self::assertSame("\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\x00\x00\x00\x00\x00\x00", $binary2, 'Binary representation of 0.0.0.0');

        // Test binary to string conversion
        self::assertSame('::ffff:192.168.1.1', Ipv4ToIpv6Mapper::binaryToString(Ipv4ToIpv6Mapper::toBinary('192.168.1.1')), 'Binary to string roundtrip');
        self::assertSame('::ffff:0.0.0.0', Ipv4ToIpv6Mapper::binaryToString(Ipv4ToIpv6Mapper::toBinary('0.0.0.0')), 'Binary to string roundtrip 0.0.0.0');

        // Test edge cases for binary to string
        $allZeros = str_repeat("\x00", 16);
        self::assertSame('::', Ipv4ToIpv6Mapper::binaryToString($allZeros), 'All zeros maps to ::');

        $allOnes = str_repeat("\xff", 16);
        self::assertSame('ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff', Ipv4ToIpv6Mapper::binaryToString($allOnes), 'All ones maps to full form');

        // Test specific IPv6 patterns
        $binary3 = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01";
        self::assertSame('::1', Ipv4ToIpv6Mapper::binaryToString($binary3), 'Loopback IPv6');

        $binary4 = "\x20\x01\x0d\xb8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01";
        self::assertSame('2001:db8::1', Ipv4ToIpv6Mapper::binaryToString($binary4), 'Documentation prefix');

        // Summary
        echo str_repeat('=', 50) . "\n";
        echo "Tests Passed: " . self::$passed . "\n";
        echo "Tests Failed: " . self::$failed . "\n";
        echo "Total Tests:  " . (self::$passed + self::$failed) . "\n";

        if (self::$failed > 0) {
            exit(1);
        }
    }
}

// Run tests
Ipv4ToIpv6MapperTest::run();
