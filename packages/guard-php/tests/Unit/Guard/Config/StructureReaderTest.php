<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Config\StructureReader
 * @uses \Toolkit\Guard\Config\Schema
 * @uses \Toolkit\Guard\Policy\PolicyException
 * @uses \Toolkit\TreeGuard\Config\ConfigScalarReader
 * @uses \Toolkit\TreeGuard\Config\ConfigStringListReader
 * @uses \Toolkit\TreeGuard\Config\ReportConfig
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\Config\RuleConfigReader
 * @uses \Toolkit\TreeGuard\Config\RuleListConfigReader
 * @uses \Toolkit\TreeGuard\Config\TreeGuardConfig
 * @uses \Toolkit\TreeGuard\TreeGuardException
 */
#[CoversClass(\Toolkit\Guard\Config\StructureReader::class)]
#[UsesClass(\Toolkit\Guard\Config\Schema::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ConfigScalarReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ConfigStringListReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ReportConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\RuleConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\RuleConfigReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\RuleListConfigReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\TreeGuardConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\TreeGuardException::class)]
final class StructureReaderTest extends TestCase
{
    public function testReadRetainsAllDirectoryRules(): void
    {
        $config = (new \Toolkit\Guard\Config\StructureReader())->read(['directories' => [['path' => '**', 'max_files' => 15, 'deny' => ['*Helper.php']]]], '/project');
        self::assertSame(15, $config->rules[0]->maxFiles);
        self::assertSame(['*Helper.php'], $config->rules[0]->deny);
    }

}
