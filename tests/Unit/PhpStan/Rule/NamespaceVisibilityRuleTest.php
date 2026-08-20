<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\ExemptCallerNamespaces;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\NamespaceLineage;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\ReferencedClassResolver;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\TypeReferenceInspector;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityAccessChecker;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityScope;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityScopeResolver;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityTagInspector;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityTagParser;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityUsageInspector;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibilityRule;
use PhpAiToolkit\PhpStan\Rule\Shared\ClassLikeKindLabel;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<NamespaceVisibilityRule>
 */
#[CoversClass(NamespaceVisibilityRule::class)]
#[UsesClass(ClassLikeKindLabel::class)]
#[UsesClass(ExemptCallerNamespaces::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(ReferencedClassResolver::class)]
#[UsesClass(TypeReferenceInspector::class)]
#[UsesClass(VisibilityAccessChecker::class)]
#[UsesClass(VisibilityErrorBuilder::class)]
#[UsesClass(VisibilityScope::class)]
#[UsesClass(VisibilityScopeResolver::class)]
#[UsesClass(VisibilityTagInspector::class)]
#[UsesClass(VisibilityTagParser::class)]
#[UsesClass(VisibilityUsageInspector::class)]
#[Medium]
final class NamespaceVisibilityRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new NamespaceVisibilityRule(['Tests\\Fixture\\NamespaceVisibility\\Exempt']);
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node::class, $this->getRule()->getNodeType());
    }

    public function testDeclarationErrorsSkipsAnonymousClasses(): void
    {
        $rule = new NamespaceVisibilityRule();

        self::assertSame([], $rule->declarationErrors(new \PhpParser\Node\Stmt\Class_(null), self::createStub(Scope::class)));
    }

    public function testProcessNodeUsagesOutsideTheScopeAreReported(): void
    {
        $message = 'Class Tests\\Fixture\\NamespaceVisibility\\Package\\NamespaceScoped is not visible from namespace "Tests\\Fixture\\NamespaceVisibility\\Outside". The declaration is marked "@visibility namespace", so it may only be used from namespace "Tests\\Fixture\\NamespaceVisibility\\Package" and its sub-namespaces. Move the caller into that namespace, or widen the declaration to "@visibility Tests\\Fixture\\NamespaceVisibility".';

        $this->analyse([__DIR__ . '/../../../Fixture/NamespaceVisibility/Outside/OutsideCaller.php'], [
            [$message, 18],
            [$message, 20],
            [$message, 25],
            [$message, 28],
            [$message, 30],
            [$message, 35],
            [$message, 40],
            [$message, 40],
            [$message, 45],
            [$message, 50],
            [
                'Call to Tests\\Fixture\\NamespaceVisibility\\Package\\MemberScoped::internalRun() is not visible from namespace "Tests\\Fixture\\NamespaceVisibility\\Outside". The declaration is marked "@visibility namespace", so it may only be used from namespace "Tests\\Fixture\\NamespaceVisibility\\Package" and its sub-namespaces. Move the caller into that namespace, or widen the declaration to "@visibility Tests\\Fixture\\NamespaceVisibility".',
                55,
            ],
            [
                'Access to property Tests\\Fixture\\NamespaceVisibility\\Package\\MemberScoped::$state is not visible from namespace "Tests\\Fixture\\NamespaceVisibility\\Outside". The declaration is marked "@visibility namespace", so it may only be used from namespace "Tests\\Fixture\\NamespaceVisibility\\Package" and its sub-namespaces. Move the caller into that namespace, or widen the declaration to "@visibility Tests\\Fixture\\NamespaceVisibility".',
                56,
            ],
            [
                'Access to property Tests\\Fixture\\NamespaceVisibility\\Package\\MemberScoped::$sharedState is not visible from namespace "Tests\\Fixture\\NamespaceVisibility\\Outside". The declaration is marked "@visibility namespace", so it may only be used from namespace "Tests\\Fixture\\NamespaceVisibility\\Package" and its sub-namespaces. Move the caller into that namespace, or widen the declaration to "@visibility Tests\\Fixture\\NamespaceVisibility".',
                57,
            ],
            [
                'Access to constant Tests\\Fixture\\NamespaceVisibility\\Package\\MemberScoped::SECRET is not visible from namespace "Tests\\Fixture\\NamespaceVisibility\\Outside". The declaration is marked "@visibility namespace", so it may only be used from namespace "Tests\\Fixture\\NamespaceVisibility\\Package" and its sub-namespaces. Move the caller into that namespace, or widen the declaration to "@visibility Tests\\Fixture\\NamespaceVisibility".',
                64,
            ],
        ]);
    }

    public function testProcessNodeTypeReferencesOutsideTheScopeAreReported(): void
    {
        $classMessage = 'Class Tests\\Fixture\\NamespaceVisibility\\Package\\NamespaceScoped is not visible from namespace "Tests\\Fixture\\NamespaceVisibility\\Outside". The declaration is marked "@visibility namespace", so it may only be used from namespace "Tests\\Fixture\\NamespaceVisibility\\Package" and its sub-namespaces. Move the caller into that namespace, or widen the declaration to "@visibility Tests\\Fixture\\NamespaceVisibility".';

        $this->analyse([__DIR__ . '/../../../Fixture/NamespaceVisibility/Outside/OutsideInheritor.php'], [
            [
                'Class Tests\\Fixture\\NamespaceVisibility\\Package\\ScopedBase is not visible from namespace "Tests\\Fixture\\NamespaceVisibility\\Outside". The declaration is marked "@visibility namespace", so it may only be used from namespace "Tests\\Fixture\\NamespaceVisibility\\Package" and its sub-namespaces. Move the caller into that namespace, or widen the declaration to "@visibility Tests\\Fixture\\NamespaceVisibility".',
                12,
            ],
            [
                'Interface Tests\\Fixture\\NamespaceVisibility\\Package\\ScopedContract is not visible from namespace "Tests\\Fixture\\NamespaceVisibility\\Outside". The declaration is marked "@visibility namespace", so it may only be used from namespace "Tests\\Fixture\\NamespaceVisibility\\Package" and its sub-namespaces. Move the caller into that namespace, or widen the declaration to "@visibility Tests\\Fixture\\NamespaceVisibility".',
                12,
            ],
            [
                'Trait Tests\\Fixture\\NamespaceVisibility\\Package\\ScopedTrait is not visible from namespace "Tests\\Fixture\\NamespaceVisibility\\Outside". The declaration is marked "@visibility namespace", so it may only be used from namespace "Tests\\Fixture\\NamespaceVisibility\\Package" and its sub-namespaces. Move the caller into that namespace, or widen the declaration to "@visibility Tests\\Fixture\\NamespaceVisibility".',
                14,
            ],
            [$classMessage, 16],
            [$classMessage, 23],
            [$classMessage, 23],
        ]);
    }

    public function testProcessNodeRootAndParentScopesAreReportedForForeignRoot(): void
    {
        $rootMessage = 'Class Tests\\Fixture\\NamespaceVisibility\\Package\\RootScoped is not visible from namespace "NamespaceVisibilityForeignRoot". The declaration is marked "@visibility root", so it may only be used from namespace "Tests" and its sub-namespaces. Move the caller into that namespace, or widen the declaration to "@visibility public".';
        $parentMessage = 'Class Tests\\Fixture\\NamespaceVisibility\\Package\\ParentScoped is not visible from namespace "NamespaceVisibilityForeignRoot". The declaration is marked "@visibility parent", so it may only be used from namespace "Tests\\Fixture\\NamespaceVisibility" and its sub-namespaces. Move the caller into that namespace, or widen the declaration to "@visibility public".';

        $this->analyse([__DIR__ . '/../../../Fixture/NamespaceVisibility/ForeignRootCaller.php'], [
            [$rootMessage, 12],
            [$rootMessage, 14],
            [$parentMessage, 17],
            [$parentMessage, 19],
        ]);
    }

    public function testProcessNodeMalformedTagsAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NamespaceVisibility/Invalid/MalformedTags.php'], [
            [
                'Fix "@visibility parrent" on class Tests\\Fixture\\NamespaceVisibility\\Invalid\\MalformedTags: one bare lowercase word is read as a scope keyword, and "parrent" is not one of "public", "root", "parent", "namespace"; write the keyword you meant, or write "\\parrent" to name the namespace.',
                10,
            ],
            [
                'Remove either "@visibility public" or the narrowing @visibility tags on constant Tests\\Fixture\\NamespaceVisibility\\Invalid\\MalformedTags::MIXED: "public" makes the declaration visible everywhere, so keeping both leaves the narrower tags with no effect.',
                16,
            ],
            [
                'Fix "@visibility 123bad" on property Tests\\Fixture\\NamespaceVisibility\\Invalid\\MalformedTags::$value: the scope has to be "public", "root", "parent", "namespace", or a namespace name such as "App\\Domain".',
                21,
            ],
        ]);
    }

    public function testProcessNodeTagsOnGlobalDeclarationsAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NamespaceVisibility/Invalid/GlobalScoped.php'], [
            [
                'Fix "@visibility parent" on class NamespaceVisibilityGlobalScoped: the declaration is in the global namespace, which has no parent namespace to open up.',
                8,
            ],
            [
                'Fix "@visibility root" on method NamespaceVisibilityGlobalScoped::rootScoped(): the declaration is in the global namespace, which has no root namespace to open up.',
                13,
            ],
            [
                'Fix "@visibility namespace" on method NamespaceVisibilityGlobalScoped::namespaceScoped(): the declaration is in the global namespace, so "namespace" covers every namespace instead of narrowing anything.',
                21,
            ],
        ]);
    }

    public function testProcessNodeParentOfRootNamespaceIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NamespaceVisibility/Invalid/SingleSegmentParent.php'], [
            [
                'Fix "@visibility parent" on class NamespaceVisibilitySingleSegment\\SingleSegmentParent: the parent of namespace "NamespaceVisibilitySingleSegment" is the global namespace, which narrows nothing; write "@visibility namespace" or name an outer namespace.',
                10,
            ],
        ]);
    }

    public function testProcessNodeUsagesInsideTheScopeAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NamespaceVisibility/Package/InsideCaller.php'], []);
    }

    public function testProcessNodeUsagesInSubNamespacesAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NamespaceVisibility/Package/Nested/NestedCaller.php'], []);
    }

    public function testProcessNodeUsagesInExemptNamespacesAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NamespaceVisibility/Exempt/ExemptCaller.php'], []);
    }

    public function testProcessNodeMalformedTagsInExemptNamespacesAreStillReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NamespaceVisibility/Exempt/ExemptMalformedTags.php'], [
            [
                'Fix "@visibility parrent" on class Tests\\Fixture\\NamespaceVisibility\\Exempt\\ExemptMalformedTags: one bare lowercase word is read as a scope keyword, and "parrent" is not one of "public", "root", "parent", "namespace"; write the keyword you meant, or write "\\parrent" to name the namespace.',
                12,
            ],
        ]);
    }
}
