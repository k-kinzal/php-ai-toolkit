<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Cli\ScopeGuardConfigPathResolver;

/**
 * @covers \Toolkit\ScopeGuard\Cli\ScopeGuardConfigPathResolver
 */
#[CoversClass(ScopeGuardConfigPathResolver::class)]
final class ScopeGuardConfigPathResolverTest extends TestCase
{
    public function testResolveJoinsARelativePathToTheWorkingDirectory(): void
    {
        self::assertSame('/project/scope.yaml', (new ScopeGuardConfigPathResolver())->resolve('/project', 'scope.yaml'));
    }

    public function testResolveKeepsAnAbsolutePath(): void
    {
        self::assertSame('/etc/scope.yaml', (new ScopeGuardConfigPathResolver())->resolve('/project', '/etc/scope.yaml'));
    }
}
