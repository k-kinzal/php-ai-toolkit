<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpAiToolkit\PhpStan\Rule\Shared\RestrictedTestNamespaceMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
