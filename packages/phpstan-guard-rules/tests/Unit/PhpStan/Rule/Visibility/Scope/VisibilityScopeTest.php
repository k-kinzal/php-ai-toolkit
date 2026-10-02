<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility\Scope;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScope;

/**
 * @covers \Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScope
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage
 */
#[CoversClass(VisibilityScope::class)]
#[UsesClass(NamespaceLineage::class)]
final class VisibilityScopeTest extends TestCase
{
    public function testDeclaredValuesReturnWrittenTags(): void
    {
        $scope = new VisibilityScope(['App\Domain'], ['namespace'], true);

        self::assertSame(['namespace'], $scope->declaredValues());
    }

    public function testPermitsOnlyConfiguredSubtrees(): void
    {
        $scope = new VisibilityScope(['App\Domain'], ['namespace'], true);

        self::assertTrue($scope->permits('App\Domain\Model'));
        self::assertFalse($scope->permits('App\Http'));
    }

    public function testDescribeTagsQuotesWrittenTags(): void
    {
        $scope = new VisibilityScope(['App\Domain'], ['namespace'], true);

        self::assertSame('"@visibility namespace"', $scope->describeTags());
    }

    public function testDescribeAllowedPhrasesNamespaceSubtree(): void
    {
        $scope = new VisibilityScope(['App\Domain'], ['namespace'], true);

        self::assertSame('namespace "App\Domain" and its sub-namespaces', $scope->describeAllowed());
    }
}
