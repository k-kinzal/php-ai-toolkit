<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Execution;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Execution\ConfigurationPlanner
 * @uses \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\Guard\Config\Configuration
 * @uses \Toolkit\Guard\Config\RuleReader
 * @uses \Toolkit\Guard\Config\Schema
 * @uses \Toolkit\Guard\Document\DataDocument
 * @uses \Toolkit\Guard\Document\Pointer
 * @uses \Toolkit\Guard\Document\Selection
 * @uses \Toolkit\Guard\Document\TomlEncoder
 * @uses \Toolkit\Guard\Document\XmlDocument
 * @uses \Toolkit\Guard\Execution\FileChange
 * @uses \Toolkit\Guard\Execution\FilePlanner
 * @uses \Toolkit\Guard\Execution\Plan
 * @uses \Toolkit\Guard\Execution\TargetPath
 * @uses \Toolkit\Guard\Policy\Constraint
 * @uses \Toolkit\Guard\Policy\PolicyException
 * @uses \Toolkit\Guard\Policy\Rule
 * @uses \Toolkit\Guard\Policy\RuleEvaluator
 * @uses \Toolkit\Guard\Reporting\Finding
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfig
 * @uses \Toolkit\TreeGuard\Config\TreeGuardConfig
 */
#[CoversClass(\Toolkit\Guard\Execution\ConfigurationPlanner::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocGuardConfig::class)]
#[UsesClass(\Toolkit\Guard\Config\Configuration::class)]
#[UsesClass(\Toolkit\Guard\Config\RuleReader::class)]
#[UsesClass(\Toolkit\Guard\Config\Schema::class)]
#[UsesClass(\Toolkit\Guard\Document\DataDocument::class)]
#[UsesClass(\Toolkit\Guard\Document\Pointer::class)]
#[UsesClass(\Toolkit\Guard\Document\Selection::class)]
#[UsesClass(\Toolkit\Guard\Document\TomlEncoder::class)]
#[UsesClass(\Toolkit\Guard\Document\XmlDocument::class)]
#[UsesClass(\Toolkit\Guard\Execution\FileChange::class)]
#[UsesClass(\Toolkit\Guard\Execution\FilePlanner::class)]
#[UsesClass(\Toolkit\Guard\Execution\Plan::class)]
#[UsesClass(\Toolkit\Guard\Execution\TargetPath::class)]
#[UsesClass(\Toolkit\Guard\Policy\Constraint::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
#[UsesClass(\Toolkit\Guard\Policy\Rule::class)]
#[UsesClass(\Toolkit\Guard\Policy\RuleEvaluator::class)]
#[UsesClass(\Toolkit\Guard\Reporting\Finding::class)]
#[UsesClass(\Toolkit\LocGuard\Config\LimitConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\LocGuardConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\TreeGuardConfig::class)]
final class ConfigurationPlannerTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testPlanRefusesPoliciesThatRewriteThemselves(): void
    {
        $root = sys_get_temp_dir() . '/guard-self-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', 'version: 1');
        $rule = (new \Toolkit\Guard\Config\RuleReader())->rule(['id' => 'self', 'file' => 'guard.yaml', 'select' => '/version', 'assert' => ['equals' => 1]]);
        $config = new \Toolkit\Guard\Config\Configuration($root, null, null, null, [$rule]);
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Execution\ConfigurationPlanner())->plan($config, $root . '/guard.yaml', true);
    }

}
