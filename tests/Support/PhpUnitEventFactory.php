<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Event\Code\TestDox;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Telemetry\Duration;
use PHPUnit\Event\Telemetry\GarbageCollectorStatus;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Telemetry\MemoryUsage;
use PHPUnit\Event\Telemetry\Snapshot;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Metadata\MetadataCollection;

/**
 * Factory for constructing PHPUnit event value objects in tests.
 *
 * Provides minimal default values for the deeply nested object
 * graphs required by PHPUnit's event system.
 */
final class PhpUnitEventFactory
{
    /**
     * Creates a TestMethod instance with minimal defaults.
     *
     * Accepts any string as the class name since test scenarios use
     * fictional class names that do not exist at runtime.
     *
     * @param non-empty-string $methodName test method name
     * @param non-empty-string $file absolute file path
     * @param non-negative-int $line line number
     */
    public static function createTestMethod(string $className, string $methodName, string $file, int $line): TestMethod
    {
        /** @var class-string $className */
        return new TestMethod(
            $className,
            $methodName,
            $file,
            $line,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );
    }

    /**
     * Creates a minimal Telemetry\Info instance for testing.
     */
    public static function createTelemetryInfo(): Info
    {
        $time = HRTime::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $gc = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $snapshot = new Snapshot($time, $memory, $memory, $gc);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);

        return new Info($snapshot, $duration, $memory, $duration, $memory);
    }
}
