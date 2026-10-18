<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use DecisionsLint\Config\Configuration;
use DecisionsLint\Provider\ProviderSettings;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Config\Configuration
 * @uses \DecisionsLint\Provider\ProviderSettings
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 */
#[CoversClass(Configuration::class)]
#[UsesClass(ProviderSettings::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
final class ConfigurationTest extends TestCase
{
    public function testActiveRulesKeepsEnabledRulesNarrowedToTheGivenIds(): void
    {
        $enabled = new Rule('a', '', 'error', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'Q?'), []);
        $disabled = new Rule('b', '', 'error', false, [], [], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'Q?'), []);
        $other = new Rule('c', '', 'error', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'Q?'), []);
        $configuration = new Configuration('/project/decisions.yaml', ProviderSettings::defaults('jev'), ['src'], ['vendor'], 'warning', [$enabled, $disabled, $other]);
        self::assertSame([$enabled, $other], $configuration->activeRules());
        self::assertSame([$other], $configuration->activeRules(['c', 'b']));
        self::assertSame('/project/decisions.yaml', $configuration->path);
        self::assertSame('/project', $configuration->directory);
        self::assertSame('jev', $configuration->provider->name);
        self::assertSame(['src'], $configuration->paths);
        self::assertSame(['vendor'], $configuration->exclude);
        self::assertSame('warning', $configuration->failOn);
        self::assertCount(3, $configuration->rules);
    }
}
