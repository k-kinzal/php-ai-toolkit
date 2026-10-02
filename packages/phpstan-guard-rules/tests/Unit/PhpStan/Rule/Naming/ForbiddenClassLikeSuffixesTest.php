<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Naming;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Naming\ForbiddenClassLikeSuffixes;

/**
 * @covers \Toolkit\PhpStan\Rule\Naming\ForbiddenClassLikeSuffixes
 */
#[CoversClass(ForbiddenClassLikeSuffixes::class)]
final class ForbiddenClassLikeSuffixesTest extends TestCase
{
    public function testMatchingSuffixReturnsConfiguredSuffix(): void
    {
        $suffixes = new ForbiddenClassLikeSuffixes(['Helper']);

        self::assertSame('Helper', $suffixes->matchingSuffix('UserHelper'));
        self::assertNull($suffixes->matchingSuffix('UserProfile'));
    }
}
