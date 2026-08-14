<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Config;

use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RuleConfig::class)]
final class RuleConfigTest extends TestCase
{
    public function testStoresRuleData(): void
    {
        $rule = new RuleConfig('src/**', 25, 20, 250, 3, ['*.php'], ['*Helper.php'], ['[A-Z]*'], ['Helpers'], ['README.md'], true, 'pascal', 'camel');

        self::assertSame('src/**', $rule->path);
        self::assertSame(25, $rule->maxFiles);
        self::assertSame(20, $rule->maxDirs);
        self::assertSame(250, $rule->maxTotalFiles);
        self::assertSame(3, $rule->maxDepth);
        self::assertSame(['*.php'], $rule->allow);
        self::assertSame(['*Helper.php'], $rule->deny);
        self::assertSame(['[A-Z]*'], $rule->allowDirs);
        self::assertSame(['Helpers'], $rule->denyDirs);
        self::assertSame(['README.md'], $rule->require);
        self::assertTrue($rule->forbidEmpty);
        self::assertSame('pascal', $rule->fileCase);
        self::assertSame('camel', $rule->dirCase);
    }

    public function testStoresAbsentConstraintsAsNull(): void
    {
        $rule = new RuleConfig('src', null, null, null, null, null, null, null, null, null, false, null, null);

        self::assertSame('src', $rule->path);
        self::assertNull($rule->maxFiles);
        self::assertNull($rule->allow);
        self::assertNull($rule->require);
        self::assertFalse($rule->forbidEmpty);
        self::assertNull($rule->fileCase);
    }
}
