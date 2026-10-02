<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Config\ConfigScalarReader;
use Toolkit\TreeGuard\Config\ConfigStringListReader;
use Toolkit\TreeGuard\Config\RuleConfig;
use Toolkit\TreeGuard\Config\RuleConfigReader;
use Toolkit\TreeGuard\TreeGuardException;

/**
 * @covers \Toolkit\TreeGuard\Config\RuleConfigReader
 * @uses \Toolkit\TreeGuard\Config\ConfigScalarReader
 * @uses \Toolkit\TreeGuard\Config\ConfigStringListReader
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\TreeGuardException
 */
#[CoversClass(RuleConfigReader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(TreeGuardException::class)]
final class RuleConfigReaderTest extends TestCase
{
    public function testReadParsesAllKeys(): void
    {
        $rule = (new RuleConfigReader())->read([
            'path' => 'src/**',
            'max_files' => 25,
            'max_dirs' => 20,
            'max_total_files' => 250,
            'max_depth' => 3,
            'allow' => ['*.php'],
            'deny' => ['*Helper.php'],
            'allow_dirs' => ['[A-Z]*'],
            'deny_dirs' => ['Helpers'],
            'require' => ['README.md'],
            'forbid_empty' => true,
            'file_case' => 'pascal',
            'dir_case' => 'kebab',
        ], 0);

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
        self::assertSame('kebab', $rule->dirCase);
    }

    public function testReadLeavesAbsentConstraintsUnchecked(): void
    {
        $rule = (new RuleConfigReader())->read(['path' => 'src'], 0);

        self::assertSame('src', $rule->path);
        self::assertNull($rule->maxFiles);
        self::assertNull($rule->maxDirs);
        self::assertNull($rule->maxTotalFiles);
        self::assertNull($rule->maxDepth);
        self::assertNull($rule->allow);
        self::assertNull($rule->deny);
        self::assertNull($rule->allowDirs);
        self::assertNull($rule->denyDirs);
        self::assertNull($rule->require);
        self::assertFalse($rule->forbidEmpty);
        self::assertNull($rule->fileCase);
        self::assertNull($rule->dirCase);
    }

    public function testReadDistinguishesEmptyAllowFromAbsent(): void
    {
        $rule = (new RuleConfigReader())->read(['path' => 'src', 'allow' => []], 0);

        self::assertSame([], $rule->allow);
        self::assertNull($rule->deny);
    }

    public function testReadRejectsNonMapping(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[1]" must be a mapping.');

        (new RuleConfigReader())->read('src', 1);
    }

    public function testReadRejectsUnknownKey(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0]" contains unsupported key "max_file".');

        (new RuleConfigReader())->read(['path' => 'src', 'max_file' => 25], 0);
    }

    public function testReadRejectsMissingPath(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[0].path" must be a non-empty string.');

        (new RuleConfigReader())->read(['max_files' => 25], 0);
    }
}
