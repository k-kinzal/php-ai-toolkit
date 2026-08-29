<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;
use Toolkit\LocGuard\Config\Policy\PolicyDefinition;
use Toolkit\LocGuard\Config\Policy\PolicyResolver;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Config\Policy\PolicyResolver
 */
#[CoversClass(PolicyResolver::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(PolicyDefinition::class)]
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
        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('extends unknown policy');

        (new PolicyResolver())->validateParents([
            'native' => new PolicyDefinition('native', 'missing', ['file.lines' => 900]),
        ]);
    }

    public function testResolveDefinitionRejectsPolicyWithNoEnabledLimits(): void
    {
        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('must enable at least one');

        (new PolicyResolver())->resolveDefinition(
            new PolicyDefinition('empty', null, ['file.lines' => null]),
            [],
        );
    }
}
