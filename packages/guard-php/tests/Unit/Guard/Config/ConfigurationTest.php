<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Config\Configuration
 * @uses \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\Guard\Policy\Rule
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfig
 * @uses \Toolkit\TreeGuard\Config\TreeGuardConfig
 */
#[CoversClass(\Toolkit\Guard\Config\Configuration::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocGuardConfig::class)]
#[UsesClass(\Toolkit\Guard\Policy\Rule::class)]
#[UsesClass(\Toolkit\LocGuard\Config\LimitConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\LocGuardConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\TreeGuardConfig::class)]
final class ConfigurationTest extends TestCase
{
    public function testKeepsExplicitlySelectedChecks(): void
    {
        $config = new \Toolkit\Guard\Config\Configuration('/project', null, null, null, []);
        self::assertSame('/project', $config->root);
        self::assertNull($config->metrics);
        self::assertSame([], $config->rules);
    }

}
