<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Tree;

use Guard\Config\Tree\ConfigScalarReader;
use Guard\Config\Tree\ConfigStringListReader;
use Guard\Config\Tree\RuleConfig;
use Guard\Config\Tree\RuleConfigReader;
use Guard\Config\Tree\RuleListConfigReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Tree\RuleListConfigReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Tree\ConfigScalarReader
 * @uses \Guard\Config\Tree\ConfigStringListReader
 * @uses \Guard\Config\Tree\RuleConfig
 * @uses \Guard\Config\Tree\RuleConfigReader
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(RuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(RuleConfigReader::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules" must be a list of mappings.');

        (new RuleListConfigReader())->read('strict');
    }

    public function testReadReportsIndexOfInvalidRule(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "rules[1]" must be a mapping.');

        (new RuleListConfigReader())->read([['path' => 'src'], 'oops']);
    }
}
