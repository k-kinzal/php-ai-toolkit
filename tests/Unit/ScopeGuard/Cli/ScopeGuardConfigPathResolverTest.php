<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Cli;

use PhpAiToolkit\ScopeGuard\Cli\ScopeGuardConfigPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
