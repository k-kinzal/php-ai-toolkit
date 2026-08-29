<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\Policy\PolicyDefinition;

/**
 * @covers \Toolkit\LocGuard\Config\Policy\PolicyDefinition
 */
#[CoversClass(PolicyDefinition::class)]
final class PolicyDefinitionTest extends TestCase
{
    public function testStoresUnresolvedPolicyValues(): void
    {
        $definition = new PolicyDefinition('native', 'standard', ['file.lines' => 900]);

        self::assertSame('native', $definition->name);
        self::assertSame('standard', $definition->extends);
        self::assertSame(['file.lines' => 900], $definition->limitPatch);
    }
}
