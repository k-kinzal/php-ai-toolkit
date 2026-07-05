<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PhpAiToolkit\LocGuard\Cli\LocGuardConfigPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocGuardConfigPathResolver::class)]
final class LocGuardConfigPathResolverTest extends TestCase
{
    public function testResolveReturnsAbsoluteOrWorkingDirectoryRelativePath(): void
    {
        self::assertSame('/tmp/project/loc.yaml', (new LocGuardConfigPathResolver())->resolve('/tmp/project', 'loc.yaml'));
        self::assertSame('/etc/loc.yaml', (new LocGuardConfigPathResolver())->resolve('/tmp/project', '/etc/loc.yaml'));
    }
}
