<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Architecture;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermRestrictions;
use Toolkit\PhpStan\Rule\Shared\Path\RulePathMatcher;
use Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer;

/**
 * @covers \Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermRestrictions
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\RulePathMatcher
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer
 */
#[CoversClass(ForbiddenFileTermRestrictions::class)]
#[UsesClass(RulePathMatcher::class)]
#[UsesClass(RulePathNormalizer::class)]
final class ForbiddenFileTermRestrictionsTest extends TestCase
{
    public function testMatchingReturnsNormalizedTermsForEveryApplicablePathPolicy(): void
    {
        $restrictions = new ForbiddenFileTermRestrictions([
            '' => ['ignored'],
            'src/Query/Abstract/*' => ['mysql', 'MYSQL', '', 'postgres'],
            'src/Query/*' => [],
        ]);

        self::assertSame([
            [
                'path' => 'src/Query/Abstract/*',
                'terms' => ['mysql', 'postgres'],
            ],
        ], $restrictions->matching('/project/src/Query/Abstract/Query.php'));
        self::assertSame([], $restrictions->matching('/project/src/Query/Concrete/Query.php'));
    }

    public function testUniqueTermsPreservesOrderAndRemovesEmptyAndCaseInsensitiveDuplicates(): void
    {
        $restrictions = new ForbiddenFileTermRestrictions();

        self::assertSame(['SQLite', 'postgres'], $restrictions->uniqueTerms(['SQLite', '', 'sqlite', 'postgres']));
    }
}
