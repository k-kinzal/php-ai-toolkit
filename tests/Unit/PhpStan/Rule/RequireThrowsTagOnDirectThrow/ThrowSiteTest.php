<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\RequireThrowsTagOnDirectThrow;

use PhpAiToolkit\PhpStan\Rule\RequireThrowsTagOnDirectThrow\ThrowSite;
use PhpParser\Node\Name;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ThrowSite::class)]
final class ThrowSiteTest extends TestCase
{
    public function testStoresThrowSiteData(): void
    {
        $thrownName = new Name('RuntimeException');
        $guardName = new Name('LogicException');

        $site = new ThrowSite([$thrownName], [$guardName], 42);

        self::assertSame([$thrownName], $site->thrownNames);
        self::assertSame([$guardName], $site->guardNames);
        self::assertSame(42, $site->line);
    }
}
