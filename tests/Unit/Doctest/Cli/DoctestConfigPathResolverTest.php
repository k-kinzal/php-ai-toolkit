<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Cli;

use PhpAiToolkit\Doctest\Cli\DoctestConfigPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DoctestConfigPathResolver::class)]
final class DoctestConfigPathResolverTest extends TestCase
{
    public function testResolveJoinsRelativePathsAndKeepsAbsoluteOnes(): void
    {
        $resolver = new DoctestConfigPathResolver();

        self::assertSame('/app/doctest.yaml', $resolver->resolve('/app', 'doctest.yaml'));
        self::assertSame('/etc/doctest.yaml', $resolver->resolve('/app', '/etc/doctest.yaml'));
    }
}
