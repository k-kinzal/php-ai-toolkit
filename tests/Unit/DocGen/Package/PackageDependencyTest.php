<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Package;

use PhpAiToolkit\DocGen\Package\PackageDependency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PackageDependency::class)]
final class PackageDependencyTest extends TestCase
{
    public function testStoresEdgeData(): void
    {
        $dependency = new PackageDependency('acme/a', 'acme/b', 'require-dev');

        self::assertSame('acme/a', $dependency->from);
        self::assertSame('acme/b', $dependency->to);
        self::assertSame('require-dev', $dependency->kind);
    }

    public function testStoresRequireKind(): void
    {
        $dependency = new PackageDependency('acme/app', 'acme/lib', 'require');

        self::assertSame('require', $dependency->kind);
    }
}
