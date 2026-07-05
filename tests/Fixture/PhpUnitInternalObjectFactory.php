<?php

declare(strict_types=1);

namespace Tests\Fixture;

use function class_exists;
use function method_exists;

use PHPUnit\Event\Telemetry\CpuTime;
use PHPUnit\Event\Telemetry\Duration;
use PHPUnit\Event\Telemetry\GarbageCollectorStatus;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Telemetry\MemoryUsage;
use PHPUnit\Event\Telemetry\Snapshot;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Runner\Extension\Facade;
use RuntimeException;

final class PhpUnitInternalObjectFactory
{
    public static function telemetryInfo(): Info
    {
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $garbageCollectorStatus = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);

        if (!class_exists(CpuTime::class)) {
            return new Info(
                new Snapshot(
                    HRTime::fromSecondsAndNanoseconds(0, 0),
                    $memory,
                    $memory,
                    $garbageCollectorStatus,
                ),
                $duration,
                $memory,
                $duration,
                $memory,
            );
        }

        $cpuTime = CpuTime::fromSecondsAndNanoseconds(0, 0);

        return new Info(
            new Snapshot(
                HRTime::fromSecondsAndNanoseconds(0, 0),
                $memory,
                $memory,
                $garbageCollectorStatus,
                $cpuTime,
                $cpuTime,
                $cpuTime,
            ),
            $duration,
            $memory,
            $duration,
            $memory,
            $cpuTime,
            $cpuTime,
            $cpuTime,
            $cpuTime,
            $cpuTime,
            $cpuTime,
        );
    }

    public static function legacyTestSuite(string $name): TestSuite
    {
        return new TestSuite($name);
    }

    public static function extensionFacade(): Facade
    {
        $facadeClass = class_exists('PHPUnit\Runner\Extension\ExtensionFacade')
            ? 'PHPUnit\Runner\Extension\ExtensionFacade'
            : Facade::class;
        $facade = new $facadeClass();

        if (!$facade instanceof Facade) {
            throw new RuntimeException('PHPUnit extension facade could not be created.');
        }

        return $facade;
    }

    public static function replacesProgressOutput(Facade $facade): bool
    {
        if (!method_exists($facade, 'replacesProgressOutput')) {
            throw new RuntimeException('PHPUnit extension facade does not expose progress replacement state.');
        }

        return $facade->replacesProgressOutput();
    }

    public static function replacesResultOutput(Facade $facade): bool
    {
        if (!method_exists($facade, 'replacesResultOutput')) {
            throw new RuntimeException('PHPUnit extension facade does not expose result replacement state.');
        }

        return $facade->replacesResultOutput();
    }
}
