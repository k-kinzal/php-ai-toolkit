<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Config;

use PhpAiToolkit\TreeGuard\Config\ConfigScalarReader;
use PhpAiToolkit\TreeGuard\Config\ConfigStringListReader;
use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Config\RuleConfigReader;
use PhpAiToolkit\TreeGuard\Config\RuleListConfigReader;
use PhpAiToolkit\TreeGuard\TreeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\TreeGuard\Config\RuleListConfigReader
 * @uses \PhpAiToolkit\TreeGuard\Config\ConfigScalarReader
 * @uses \PhpAiToolkit\TreeGuard\Config\ConfigStringListReader
 * @uses \PhpAiToolkit\TreeGuard\Config\RuleConfig
 * @uses \PhpAiToolkit\TreeGuard\Config\RuleConfigReader
 * @uses \PhpAiToolkit\TreeGuard\TreeGuardException
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
