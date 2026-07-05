<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Legacy;

use PhpAiToolkit\PhpUnit\TestReporter\Legacy\LegacyTestDescriptorFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(LegacyTestDescriptorFactory::class)]
final class LegacyTestDescriptorFactoryTest extends TestCase
{
    public function testFromTestExtractsTestCaseNameAndLocation(): void
    {
        $factory = new LegacyTestDescriptorFactory();

        $descriptor = $factory->fromTest(new self(__FUNCTION__));

        self::assertSame(self::class . '::' . __FUNCTION__, $descriptor->id);
        self::assertSame(self::class . '::' . __FUNCTION__, $descriptor->name);
        self::assertSame(__FILE__, $descriptor->file);
        self::assertGreaterThan(0, $descriptor->line);
    }

    public function testFromTestFallsBackForPlainObjects(): void
    {
        $factory = new LegacyTestDescriptorFactory();

        $descriptor = $factory->fromTest(new stdClass());

        self::assertSame(stdClass::class, $descriptor->id);
        self::assertSame('', $descriptor->file);
        self::assertSame(0, $descriptor->line);
    }
}
