<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Config;

use PhpAiToolkit\TreeGuard\Config\ConfigStringListReader;
use PhpAiToolkit\TreeGuard\TreeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigStringListReader::class)]
#[UsesClass(TreeGuardException::class)]
final class ConfigStringListReaderTest extends TestCase
{
    public function testReadReturnsListOfStrings(): void
    {
        self::assertSame(['src', 'tests'], (new ConfigStringListReader())->read(['paths' => ['src', 'tests']], 'paths', ['src'], ''));
    }

    public function testReadReturnsDefaultWhenAbsent(): void
    {
        self::assertSame(['src'], (new ConfigStringListReader())->read([], 'paths', ['src'], ''));
    }

    public function testReadRejectsNonListValue(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "paths" must be a list of strings.');

        (new ConfigStringListReader())->read(['paths' => 'src'], 'paths', ['src'], '');
    }

    public function testReadRejectsNonStringEntry(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].allow" must be a list of strings.');

        (new ConfigStringListReader())->read(['allow' => [1]], 'allow', [], 'rules[0]');
    }

    public function testReadRejectsEmptyStringEntry(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "exclude" must be a list of strings.');

        (new ConfigStringListReader())->read(['exclude' => ['']], 'exclude', [], '');
    }

    public function testReadOptionalReturnsNullWhenAbsent(): void
    {
        self::assertNull((new ConfigStringListReader())->readOptional([], 'allow', 'rules[0]'));
    }

    public function testReadOptionalReturnsEmptyList(): void
    {
        self::assertSame([], (new ConfigStringListReader())->readOptional(['allow' => []], 'allow', 'rules[0]'));
    }

    public function testReadOptionalReturnsListOfStrings(): void
    {
        self::assertSame(['*.php'], (new ConfigStringListReader())->readOptional(['allow' => ['*.php']], 'allow', 'rules[0]'));
    }

    public function testReadOptionalRejectsExplicitNull(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].allow" must be a list of strings.');

        (new ConfigStringListReader())->readOptional(['allow' => null], 'allow', 'rules[0]');
    }

    public function testLabelJoinsContextAndKey(): void
    {
        self::assertSame('rules[2].deny', (new ConfigStringListReader())->label('rules[2]', 'deny'));
        self::assertSame('exclude', (new ConfigStringListReader())->label('', 'exclude'));
    }
}
