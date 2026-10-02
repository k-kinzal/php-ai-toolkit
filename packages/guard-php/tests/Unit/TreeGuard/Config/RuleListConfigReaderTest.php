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
use Toolkit\TreeGuard\Config\RuleListConfigReader;
use Toolkit\TreeGuard\TreeGuardException;

/**
 * @covers \Toolkit\TreeGuard\Config\RuleListConfigReader
 * @uses \Toolkit\TreeGuard\Config\ConfigScalarReader
 * @uses \Toolkit\TreeGuard\Config\ConfigStringListReader
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\Config\RuleConfigReader
 * @uses \Toolkit\TreeGuard\TreeGuardException
 */
#[CoversClass(RuleListConfigReader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(RuleConfigReader::class)]
#[UsesClass(TreeGuardException::class)]
final class RuleListConfigReaderTest extends TestCase
{
    public function testReadReturnsEmptyListForEmptyInput(): void
    {
        self::assertSame([], (new RuleListConfigReader())->read([]));
    }

    public function testReadReadsRulesInDeclarationOrder(): void
    {
        $rules = (new RuleListConfigReader())->read([
            ['path' => 'src', 'max_files' => 10],
            ['path' => 'tests', 'max_dirs' => 5],
        ]);

        self::assertCount(2, $rules);
        self::assertSame('src', $rules[0]->path);
        self::assertSame(10, $rules[0]->maxFiles);
        self::assertSame('tests', $rules[1]->path);
        self::assertSame(5, $rules[1]->maxDirs);
    }

    public function testReadRejectsNonListValue(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules" must be a list of mappings.');

        (new RuleListConfigReader())->read('strict');
    }

    public function testReadReportsIndexOfInvalidRule(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[1]" must be a mapping.');

        (new RuleListConfigReader())->read([['path' => 'src'], 'oops']);
    }
}
