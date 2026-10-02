<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Cli\DocGuardConfigPathResolver;

/**
 * @covers \Toolkit\DocGuard\Cli\DocGuardConfigPathResolver
 */
#[CoversClass(DocGuardConfigPathResolver::class)]
final class DocGuardConfigPathResolverTest extends TestCase
{
    public function testResolveKeepsAbsolutePathsAndJoinsRelativePaths(): void
    {
        self::assertSame('/config/doc-guard.yaml', (new DocGuardConfigPathResolver())->resolve('/project', '/config/doc-guard.yaml'));
        self::assertSame('/project/packages/a/doc-guard.yaml', (new DocGuardConfigPathResolver())->resolve('/project', 'packages/a/doc-guard.yaml'));
    }
}
