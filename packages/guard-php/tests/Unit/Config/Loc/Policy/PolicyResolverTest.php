<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Loc\Policy;

use Guard\Config\Loc\LimitConfig;
use Guard\Config\Loc\Policy\PolicyConfig;
use Guard\Config\Loc\Policy\PolicyDefinition;
use Guard\Config\Loc\Policy\PolicyResolver;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Loc\Policy\PolicyResolver
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 * @uses \Guard\Config\Loc\Policy\PolicyDefinition
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(PolicyResolver::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(PolicyDefinition::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class PolicyResolverTest extends TestCase
{
    public function testResolveAppliesInheritanceWithoutDeclarationOrder(): void
    {
        $policies = (new PolicyResolver())->resolve([
            'native' => new PolicyDefinition('native', 'standard', ['file.lines' => 900]),
            'standard' => new PolicyDefinition('standard', null, [
                'file.lines' => 500,
                'method.lines' => 50,
            ]),
        ]);

        self::assertSame(900, $policies['native']->limits->maxFileLines);
        self::assertSame(50, $policies['native']->limits->maxMethodLines);
    }

    public function testValidateParentsRejectsUnknownParent(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('extends unknown policy');

        (new PolicyResolver())->validateParents([
            'native' => new PolicyDefinition('native', 'missing', ['file.lines' => 900]),
        ]);
    }

    public function testResolveDefinitionRejectsPolicyWithNoEnabledLimits(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('must enable at least one');

        (new PolicyResolver())->resolveDefinition(
            new PolicyDefinition('empty', null, ['file.lines' => null]),
            [],
        );
    }
}
