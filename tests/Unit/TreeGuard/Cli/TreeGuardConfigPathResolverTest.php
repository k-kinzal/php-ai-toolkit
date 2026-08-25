<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Cli;

use PhpAiToolkit\TreeGuard\Cli\TreeGuardConfigPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\TreeGuard\Cli\TreeGuardConfigPathResolver
 */
#[CoversClass(TreeGuardConfigPathResolver::class)]
final class TreeGuardConfigPathResolverTest extends TestCase
{
    public function testResolveJoinsRelativePathWithWorkingDirectory(): void
    {
        self::assertSame('/project/tree.yaml', (new TreeGuardConfigPathResolver())->resolve('/project', 'tree.yaml'));
    }

    public function testResolveKeepsAbsolutePath(): void
    {
        self::assertSame('/etc/tree.yaml', (new TreeGuardConfigPathResolver())->resolve('/project', '/etc/tree.yaml'));
    }
}
