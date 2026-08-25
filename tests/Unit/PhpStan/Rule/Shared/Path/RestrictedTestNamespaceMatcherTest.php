<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared\Path;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\Path\RestrictedTestNamespaceMatcher;

/**
 * @covers \Toolkit\PhpStan\Rule\Shared\Path\RestrictedTestNamespaceMatcher
 */
#[CoversClass(RestrictedTestNamespaceMatcher::class)]
final class RestrictedTestNamespaceMatcherTest extends TestCase
{
    public function testMatchesReturnsTrueForRestrictedTestNamespace(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Example');
        $class->namespacedName = new \PhpParser\Node\Name('Tests\Unit\Example');

        self::assertTrue((new RestrictedTestNamespaceMatcher())->matches($class));
    }
}
