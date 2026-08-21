<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Filesystem;

use PhpAiToolkit\Doctest\Filesystem\DoctestPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DoctestPathResolver::class)]
final class DoctestPathResolverTest extends TestCase
{
    public function testAbsoluteJoinsRelativePathsAndKeepsAbsoluteOnes(): void
    {
        $resolver = new DoctestPathResolver();

        self::assertSame('/app/src', $resolver->absolute('/app', 'src'));
        self::assertSame('/app/src', $resolver->absolute('/app', 'src/'));
        self::assertSame('/opt/src', $resolver->absolute('/app', '/opt/src/'));
    }

    public function testRelativeStripsTheRootAndLeavesOutsidePathsAlone(): void
    {
        $resolver = new DoctestPathResolver();

        self::assertSame('src/Ledger.php', $resolver->relative('/app', '/app/src/Ledger.php'));
        self::assertSame('src/Ledger.php', $resolver->relative('/app/', '/app/src/Ledger.php'));
        self::assertSame('/opt/other.php', $resolver->relative('/app', '/opt/other.php'));
    }
}
