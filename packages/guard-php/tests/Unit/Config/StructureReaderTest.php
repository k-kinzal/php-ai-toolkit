<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\StructureReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Schema
 * @uses \Guard\Config\Tree\ConfigScalarReader
 * @uses \Guard\Config\Tree\ConfigStringListReader
 * @uses \Guard\Config\Tree\RuleConfig
 * @uses \Guard\Config\Tree\RuleConfigReader
 * @uses \Guard\Config\Tree\RuleListConfigReader
 * @uses \Guard\Config\Tree\StructureConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Config\StructureReader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Config\Tree\ConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Tree\ConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Tree\RuleConfig::class)]
#[UsesClass(\Guard\Config\Tree\RuleConfigReader::class)]
#[UsesClass(\Guard\Config\Tree\RuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Tree\StructureConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class StructureReaderTest extends TestCase
{
    public function testReadRetainsAllDirectoryRules(): void
    {
        $config = (new \Guard\Config\StructureReader())->read(['directories' => [['path' => '**', 'max_files' => 15, 'deny' => ['*Helper.php']]]], '/project');
        self::assertSame(15, $config->rules[0]->maxFiles);
        self::assertSame(['*Helper.php'], $config->rules[0]->deny);
    }

}
