<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\NamespaceVisibility;

use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\NamespaceLineage;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(VisibilityErrorBuilder::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(VisibilityScope::class)]
final class VisibilityErrorBuilderTest extends TestCase
{
    public function testOutOfScopeNamesTheDeclarationAndTheWidening(): void
    {
        $error = (new VisibilityErrorBuilder())->outOfScope(
            'Call to App\\Domain\\Order::place()',
            new VisibilityScope(['App\\Domain'], ['namespace'], true),
            'App\\Http',
            'App\\Domain',
            7
        );

        self::assertSame(
            'Call to App\\Domain\\Order::place() is not visible from namespace "App\\Http". The declaration is marked "@visibility namespace", so it may only be used from namespace "App\\Domain" and its sub-namespaces. Move the caller into that namespace, or widen the declaration to "@visibility App".',
            $error->getMessage()
        );
    }

    public function testOutOfScopeUsesTheVisibilityIdentifier(): void
    {
        $error = (new VisibilityErrorBuilder())->outOfScope(
            'Class App\\Domain\\Order',
            new VisibilityScope(['App\\Domain'], ['namespace'], true),
            'App\\Http',
            'App\\Domain',
            7
        );

        self::assertSame('customRules.namespaceVisibility', $error->getIdentifier());
    }

    public function testTagProblemQuotesTheTagAndTheReason(): void
    {
        $error = (new VisibilityErrorBuilder())->tagProblem('class App\\Domain\\Order', 'parrent', 'it names nothing', 3);

        self::assertSame('Fix "@visibility parrent" on class App\\Domain\\Order: it names nothing.', $error->getMessage());
    }

    public function testTagProblemUsesTheTagIdentifier(): void
    {
        $error = (new VisibilityErrorBuilder())->tagProblem('class App\\Domain\\Order', 'parrent', 'it names nothing', 3);

        self::assertSame('customRules.namespaceVisibilityTag', $error->getIdentifier());
    }

    public function testContradictoryTagsExplainsWhichTagToRemove(): void
    {
        $error = (new VisibilityErrorBuilder())->contradictoryTags('class App\\Domain\\Order', 3);

        self::assertSame(
            'Remove either "@visibility public" or the narrowing @visibility tags on class App\\Domain\\Order: "public" makes the declaration visible everywhere, so keeping both leaves the narrower tags with no effect.',
            $error->getMessage()
        );
    }

    public function testDescribeNamespaceNamesTheGlobalNamespace(): void
    {
        self::assertSame('the global namespace', (new VisibilityErrorBuilder())->describeNamespace(''));
    }

    public function testDescribeNamespaceQuotesANamedNamespace(): void
    {
        self::assertSame('namespace "App\\Domain"', (new VisibilityErrorBuilder())->describeNamespace('App\\Domain'));
    }

    public function testDescribeAllowedJoinsSeveralNamespaces(): void
    {
        self::assertSame(
            'namespaces "App\\Domain", "App\\Http" and their sub-namespaces',
            (new VisibilityErrorBuilder())->describeAllowed(new VisibilityScope(['App\\Domain', 'App\\Http'], ['namespace'], true))
        );
    }

    public function testWideningForNamesTheSharedAncestor(): void
    {
        self::assertSame('App', (new VisibilityErrorBuilder())->wideningFor('App\\Domain', 'App\\Http'));
    }

    public function testWideningForFallsBackToPublic(): void
    {
        self::assertSame('public', (new VisibilityErrorBuilder())->wideningFor('App\\Domain', 'Other\\Place'));
    }
}
