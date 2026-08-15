<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PhpAiToolkit\DocGen\Cli\DocGenConfigPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocGenConfigPathResolver::class)]
final class DocGenConfigPathResolverTest extends TestCase
{
    public function testResolveKeepsAbsolutePath(): void
    {
        self::assertSame('/etc/doc.yaml', (new DocGenConfigPathResolver())->resolve('/work', '/etc/doc.yaml'));
    }

    public function testResolveJoinsRelativePathToWorkingDirectory(): void
    {
        self::assertSame('/work/doc.yaml', (new DocGenConfigPathResolver())->resolve('/work', 'doc.yaml'));
        self::assertSame('/work/conf/doc.yaml', (new DocGenConfigPathResolver())->resolve('/work/', 'conf/doc.yaml'));
    }
}
