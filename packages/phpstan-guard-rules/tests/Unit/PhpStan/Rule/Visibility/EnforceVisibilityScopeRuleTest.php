<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility;

use Override;
use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\Shared\ClassLikeKindLabel;
use Toolkit\PhpStan\Rule\Visibility\EnforceVisibilityScopeRule;
use Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage;
use Toolkit\PhpStan\Rule\Visibility\Scope\ScopeProblemReader;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScope;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScopeResolver;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityTagParser;
use Toolkit\PhpStan\Rule\Visibility\TypeNameReader;
use Toolkit\PhpStan\Rule\Visibility\VisibilityDeclarationCollector;
use Toolkit\PhpStan\Rule\Visibility\VisibilityDeclarationIndex;
use Toolkit\PhpStan\Rule\Visibility\VisibilityInspector;
use Toolkit\PhpStan\Rule\Visibility\VisibilityReferenceCollector;
use Toolkit\PhpStan\Rule\Visibility\VisibilityRuleErrorBuilder;

/**
 * @extends RuleTestCase<EnforceVisibilityScopeRule>
 * @covers \Toolkit\PhpStan\Rule\Visibility\EnforceVisibilityScopeRule
 * @uses \Toolkit\PhpStan\Rule\Shared\ClassLikeKindLabel
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\ScopeProblemReader
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScope
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScopeResolver
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityTagParser
 * @uses \Toolkit\PhpStan\Rule\Visibility\TypeNameReader
 * @uses \Toolkit\PhpStan\Rule\Visibility\VisibilityDeclarationCollector
 * @uses \Toolkit\PhpStan\Rule\Visibility\VisibilityDeclarationIndex
 * @uses \Toolkit\PhpStan\Rule\Visibility\VisibilityInspector
 * @uses \Toolkit\PhpStan\Rule\Visibility\VisibilityReferenceCollector
 * @uses \Toolkit\PhpStan\Rule\Visibility\VisibilityRuleErrorBuilder
 * @medium
 */
#[CoversClass(EnforceVisibilityScopeRule::class)]
#[UsesClass(ClassLikeKindLabel::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(ScopeProblemReader::class)]
#[UsesClass(VisibilityScope::class)]
#[UsesClass(VisibilityScopeResolver::class)]
#[UsesClass(VisibilityTagParser::class)]
#[UsesClass(TypeNameReader::class)]
#[UsesClass(VisibilityDeclarationCollector::class)]
#[UsesClass(VisibilityDeclarationIndex::class)]
#[UsesClass(VisibilityInspector::class)]
#[UsesClass(VisibilityReferenceCollector::class)]
#[UsesClass(VisibilityRuleErrorBuilder::class)]
#[Medium]
final class EnforceVisibilityScopeRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new EnforceVisibilityScopeRule([]);
    }

    /**
     * @return list<Collector<\PhpParser\Node, mixed>>
     */
    #[Override]
    protected function getCollectors(): array
    {
        return [new VisibilityDeclarationCollector(), new VisibilityReferenceCollector()];
    }

    public function testGetNodeTypeReturnsCollectedDataNode(): void
    {
        self::assertSame(\PHPStan\Node\CollectedDataNode::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeReportsEveryUnusableTag(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/VisibilityScope/project/src/Invalid/MalformedTags.php'], [
            [
                'Fix "@visibility parrent" on class Tests\Fixture\VisibilityScope\Invalid\MalformedTags: one bare lowercase word is read as a scope keyword, and "parrent" is not one of "public", "root", "parent", "namespace"; write the keyword you meant, or write "\parrent" to name the namespace.',
                10,
            ],
            [
                'Remove either "@visibility public" or the narrowing @visibility tags on constant Tests\Fixture\VisibilityScope\Invalid\MalformedTags::MIXED: "public" makes the declaration visible everywhere, so keeping both leaves the narrower tags with no effect.',
                16,
            ],
            [
                'Fix "@visibility 123bad" on method Tests\Fixture\VisibilityScope\Invalid\MalformedTags::unusable(): the scope has to be "public", "root", "parent", "namespace", or a namespace name such as "App\Domain".',
                21,
            ],
        ]);
    }

    public function testRuleReportsEveryWrittenReferenceOutsideClassScope(): void
    {
        $this->analyse([
            __DIR__ . '/../../../../Fixture/VisibilityScope/project/src/Package/NamespaceScoped.php',
            __DIR__ . '/../../../../Fixture/VisibilityScope/project/src/Outside/OutsideCaller.php',
        ], [
            [
                'Class Tests\Fixture\VisibilityScope\Package\NamespaceScoped is not visible from namespace "Tests\Fixture\VisibilityScope\Outside": the declaration is marked "@visibility namespace", so it may only be named from namespace "Tests\Fixture\VisibilityScope\Package" and its sub-namespaces. Move this instantiation into that namespace, or widen the declaration to "@visibility Tests\Fixture\VisibilityScope".',
                17,
            ],
            [
                'Class Tests\Fixture\VisibilityScope\Package\NamespaceScoped is not visible from namespace "Tests\Fixture\VisibilityScope\Outside": the declaration is marked "@visibility namespace", so it may only be named from namespace "Tests\Fixture\VisibilityScope\Package" and its sub-namespaces. Move this constant access into that namespace, or widen the declaration to "@visibility Tests\Fixture\VisibilityScope".',
                22,
            ],
            [
                'Class Tests\Fixture\VisibilityScope\Package\NamespaceScoped is not visible from namespace "Tests\Fixture\VisibilityScope\Outside": the declaration is marked "@visibility namespace", so it may only be named from namespace "Tests\Fixture\VisibilityScope\Package" and its sub-namespaces. Move this static property access into that namespace, or widen the declaration to "@visibility Tests\Fixture\VisibilityScope".',
                27,
            ],
            [
                'Class Tests\Fixture\VisibilityScope\Package\NamespaceScoped is not visible from namespace "Tests\Fixture\VisibilityScope\Outside": the declaration is marked "@visibility namespace", so it may only be named from namespace "Tests\Fixture\VisibilityScope\Package" and its sub-namespaces. Move this static call into that namespace, or widen the declaration to "@visibility Tests\Fixture\VisibilityScope".',
                32,
            ],
            [
                'Class Tests\Fixture\VisibilityScope\Package\NamespaceScoped is not visible from namespace "Tests\Fixture\VisibilityScope\Outside": the declaration is marked "@visibility namespace", so it may only be named from namespace "Tests\Fixture\VisibilityScope\Package" and its sub-namespaces. Move this instanceof check into that namespace, or widen the declaration to "@visibility Tests\Fixture\VisibilityScope".',
                37,
            ],
            [
                'Class Tests\Fixture\VisibilityScope\Package\NamespaceScoped is not visible from namespace "Tests\Fixture\VisibilityScope\Outside": the declaration is marked "@visibility namespace", so it may only be named from namespace "Tests\Fixture\VisibilityScope\Package" and its sub-namespaces. Move this class name reference into that namespace, or widen the declaration to "@visibility Tests\Fixture\VisibilityScope".',
                42,
            ],
        ]);
    }
}
