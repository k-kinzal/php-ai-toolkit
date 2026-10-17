<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Matching;

use Guard\Collect\Matching\GlobMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Matching\GlobMatcher
 */
#[CoversClass(GlobMatcher::class)]
final class GlobMatcherTest extends TestCase
{
    public function testAdvanceKeepsDoubleStarAndLiteralRoutesWithoutFollowingWildcardLinks(): void
    {
        $matcher = new GlobMatcher();
        self::assertSame([0], $matcher->advance(['**', '*.md'], [0], 'docs', true, false));
        self::assertSame([2], $matcher->advance(['**', '*.md'], [0], 'A.md', false, false));
        self::assertSame([], $matcher->advance(['**', '*.md'], [0], 'link', true, true));
        self::assertSame([], $matcher->advance(['**', '*.md'], [0], '.hidden', true, false));
        self::assertSame([1], $matcher->advance(['.hidden', '*.md'], [0], '.hidden', true, false));
    }
}
