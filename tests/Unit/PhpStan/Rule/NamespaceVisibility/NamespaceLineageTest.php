<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\NamespaceVisibility;

use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\NamespaceLineage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NamespaceLineage::class)]
final class NamespaceLineageTest extends TestCase
{
    public function testOfReturnsNamespaceOfQualifiedName(): void
    {
        self::assertSame('App\\Domain', (new NamespaceLineage())->of('App\\Domain\\User'));
    }

    public function testOfIgnoresLeadingSeparator(): void
    {
        self::assertSame('App\\Domain', (new NamespaceLineage())->of('\\App\\Domain\\User'));
    }

    public function testOfReturnsEmptyNamespaceForGlobalName(): void
    {
        self::assertSame('', (new NamespaceLineage())->of('User'));
    }

    public function testParentOfReturnsEnclosingNamespace(): void
    {
        self::assertSame('App', (new NamespaceLineage())->parentOf('App\\Domain'));
    }

    public function testParentOfReturnsGlobalNamespaceForSingleSegment(): void
    {
        self::assertSame('', (new NamespaceLineage())->parentOf('App'));
    }

    public function testParentOfReturnsNullForGlobalNamespace(): void
    {
        self::assertNull((new NamespaceLineage())->parentOf(''));
    }

    public function testRootOfReturnsOutermostSegment(): void
    {
        self::assertSame('App', (new NamespaceLineage())->rootOf('App\\Domain\\User'));
    }

    public function testRootOfReturnsNullForGlobalNamespace(): void
    {
        self::assertNull((new NamespaceLineage())->rootOf(''));
    }

    public function testCoversAcceptsSameNamespace(): void
    {
        self::assertTrue((new NamespaceLineage())->covers('App\\Domain', 'App\\Domain'));
    }

    public function testCoversAcceptsSubNamespace(): void
    {
        self::assertTrue((new NamespaceLineage())->covers('App\\Domain', 'App\\Domain\\Order'));
    }

    public function testCoversRejectsNamespaceWithSharedPrefix(): void
    {
        self::assertFalse((new NamespaceLineage())->covers('App\\Domain', 'App\\DomainService'));
    }

    public function testCoversAcceptsEverythingFromGlobalNamespace(): void
    {
        self::assertTrue((new NamespaceLineage())->covers('', 'App\\Domain'));
    }

    public function testCommonAncestorOfReturnsSharedPrefix(): void
    {
        self::assertSame('App', (new NamespaceLineage())->commonAncestorOf('App\\Domain\\Order', 'App\\Http'));
    }

    public function testCommonAncestorOfStopsAtSegmentBoundary(): void
    {
        self::assertSame('', (new NamespaceLineage())->commonAncestorOf('App', 'Application'));
    }

    public function testCommonAncestorOfReturnsGlobalNamespaceWithoutSharedSegment(): void
    {
        self::assertSame('', (new NamespaceLineage())->commonAncestorOf('App\\Domain', ''));
    }
}
