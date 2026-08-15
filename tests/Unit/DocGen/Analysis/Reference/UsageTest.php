<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Reference;

use PhpAiToolkit\DocGen\Analysis\Reference\Usage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Usage::class)]
final class UsageTest extends TestCase
{
    public function testStoresRecordedReferenceData(): void
    {
        $usage = new Usage('Demo\Greeter', 'greet', 'method-call', 'Demo\App', 'run', 'src/App.php', 12, false);

        self::assertSame('Demo\Greeter', $usage->targetFqcn);
        self::assertSame('greet', $usage->member);
        self::assertSame('method-call', $usage->kind);
        self::assertSame('Demo\App', $usage->fromFqcn);
        self::assertSame('run', $usage->fromMember);
        self::assertSame('src/App.php', $usage->file);
        self::assertSame(12, $usage->line);
        self::assertFalse($usage->fromDev);
    }

    public function testStoresNullableOriginFieldsAndDevFlag(): void
    {
        $usage = new Usage('Demo\Greeter', null, 'new', null, null, 'tests/GreeterTest.php', 3, true);

        self::assertNull($usage->member);
        self::assertNull($usage->fromFqcn);
        self::assertNull($usage->fromMember);
        self::assertTrue($usage->fromDev);
    }
}
