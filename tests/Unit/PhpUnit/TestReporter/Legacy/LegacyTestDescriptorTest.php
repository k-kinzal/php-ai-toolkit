<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Legacy;

use PhpAiToolkit\PhpUnit\TestReporter\Legacy\LegacyTestDescriptor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LegacyTestDescriptor::class)]
final class LegacyTestDescriptorTest extends TestCase
{
    public function testAllPropertiesAccessibleAfterCreation(): void
    {
        $descriptor = new LegacyTestDescriptor('T::m', 'T::m', '/tmp/T.php', 12);

        self::assertSame('T::m', $descriptor->id);
        self::assertSame('T::m', $descriptor->name);
        self::assertSame('/tmp/T.php', $descriptor->file);
        self::assertSame(12, $descriptor->line);
    }
}
