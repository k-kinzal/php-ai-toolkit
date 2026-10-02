<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Filesystem\DocGuardPathResolver;

/**
 * @covers \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 */
#[CoversClass(DocGuardPathResolver::class)]
final class DocGuardPathResolverTest extends TestCase
{
    public function testResolveJoinsRelativePaths(): void
    {
        self::assertSame('/project/docs/guide.md', (new DocGuardPathResolver())->resolve('/project/', 'docs/guide.md'));
        self::assertSame('/elsewhere/README.md', (new DocGuardPathResolver())->resolve('/project', '/elsewhere/README.md'));
    }

    public function testNormalizeRemovesRedundantSegments(): void
    {
        self::assertSame('docs/guide.md', (new DocGuardPathResolver())->normalize('./././docs//guide.md'));
        self::assertSame('docs', (new DocGuardPathResolver())->normalize('docs/'));
        self::assertSame('.', (new DocGuardPathResolver())->normalize('./'));
        self::assertSame('../../README.md', (new DocGuardPathResolver())->normalize('../../README.md'));
        self::assertSame('/', (new DocGuardPathResolver())->normalize('/'));
    }
}
