<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Scope;

use PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\ScopeProblemReader;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScopeProblemReader::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(VisibilityScopeResolver::class)]
#[UsesClass(VisibilityTagParser::class)]
final class ScopeProblemReaderTest extends TestCase
{
    public function testProblemAcceptsPublicKeyword(): void
    {
        self::assertNull((new ScopeProblemReader())->problem('public', 'App\\Domain'));
    }

    public function testProblemAcceptsNamespaceKeyword(): void
    {
        self::assertNull((new ScopeProblemReader())->problem('namespace', 'App\\Domain'));
    }

    public function testProblemRejectsNamespaceKeywordInGlobalNamespace(): void
    {
        self::assertSame(
            'the declaration is in the global namespace, so "namespace" covers every namespace instead of narrowing anything',
            (new ScopeProblemReader())->problem('namespace', '')
        );
    }

    public function testProblemAcceptsParentOfNestedNamespace(): void
    {
        self::assertNull((new ScopeProblemReader())->problem('parent', 'App\\Domain'));
    }

    public function testProblemAcceptsRootOfNamedNamespace(): void
    {
        self::assertNull((new ScopeProblemReader())->problem('root', 'App\\Domain'));
    }

    public function testProblemRejectsRootKeywordInGlobalNamespace(): void
    {
        self::assertSame(
            'the declaration is in the global namespace, which has no root namespace to open up',
            (new ScopeProblemReader())->problem('root', '')
        );
    }

    public function testProblemRejectsMistypedKeyword(): void
    {
        self::assertSame(
            'one bare lowercase word is read as a scope keyword, and "parrent" is not one of "public", "root", "parent", "namespace"; write the keyword you meant, or write "\\parrent" to name the namespace',
            (new ScopeProblemReader())->problem('parrent', 'App\\Domain')
        );
    }

    public function testParentProblemRejectsGlobalNamespace(): void
    {
        self::assertSame(
            'the declaration is in the global namespace, which has no parent namespace to open up',
            (new ScopeProblemReader())->parentProblem('')
        );
    }

    public function testParentProblemRejectsRootNamespace(): void
    {
        self::assertSame(
            'the parent of namespace "App" is the global namespace, which narrows nothing; write "@visibility namespace" or name an outer namespace',
            (new ScopeProblemReader())->parentProblem('App')
        );
    }

    public function testParentProblemAcceptsNestedNamespace(): void
    {
        self::assertNull((new ScopeProblemReader())->parentProblem('App\\Domain'));
    }

    public function testNamespaceProblemRejectsUnusableValue(): void
    {
        self::assertSame(
            'the scope has to be "public", "root", "parent", "namespace", or a namespace name such as "App\\Domain"',
            (new ScopeProblemReader())->namespaceProblem('123bad')
        );
    }

    public function testNamespaceProblemAcceptsEscapedLowercaseNamespace(): void
    {
        self::assertNull((new ScopeProblemReader())->namespaceProblem('\\parrent'));
    }

    public function testNamespaceProblemAcceptsQualifiedNamespace(): void
    {
        self::assertNull((new ScopeProblemReader())->namespaceProblem('App\\Domain'));
    }
}
