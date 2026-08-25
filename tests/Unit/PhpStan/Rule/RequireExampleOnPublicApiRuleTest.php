<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\RequireExampleOnPublicApiRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<RequireExampleOnPublicApiRule>
 * @covers \PhpAiToolkit\PhpStan\Rule\RequireExampleOnPublicApiRule
 */
#[CoversClass(RequireExampleOnPublicApiRule::class)]
#[Medium]
final class RequireExampleOnPublicApiRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new RequireExampleOnPublicApiRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassLike::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeClassDeclaredPublicWithoutExampleIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireExampleOnPublicApi/MissingClassExample.php'], [
            [
                'Add an @example block to class MissingClassExample: it is declared public API with "@visibility public", so it must document at least one example doctest can run. Write the example as an "@example" tag followed by indented code lines, or as a fenced php block, and assert on it with "// => value", "// Output: text", or "// throws ExceptionClass".',
                12,
            ],
        ]);
    }

    public function testProcessNodeMembersDeclaredPublicWithoutExampleAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireExampleOnPublicApi/MissingMemberExample.php'], [
            [
                'Add an @example block to constant MissingMemberExample::VERSION: it is declared public API with "@visibility public", so it must document at least one example doctest can run. Write the example as an "@example" tag followed by indented code lines, or as a fenced php block, and assert on it with "// => value", "// Output: text", or "// throws ExceptionClass".',
                17,
            ],
            [
                'Add an @example block to property MissingMemberExample::$name: it is declared public API with "@visibility public", so it must document at least one example doctest can run. Write the example as an "@example" tag followed by indented code lines, or as a fenced php block, and assert on it with "// => value", "// Output: text", or "// throws ExceptionClass".',
                24,
            ],
            [
                'Add an @example block to method MissingMemberExample::run(): it is declared public API with "@visibility public", so it must document at least one example doctest can run. Write the example as an "@example" tag followed by indented code lines, or as a fenced php block, and assert on it with "// => value", "// Output: text", or "// throws ExceptionClass".',
                31,
            ],
        ]);
    }

    public function testProcessNodeEnumCaseDeclaredPublicWithoutExampleIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireExampleOnPublicApi/MissingEnumCaseExample.php'], [
            [
                'Add an @example block to enum case MissingEnumCaseExample::Only: it is declared public API with "@visibility public", so it must document at least one example doctest can run. Write the example as an "@example" tag followed by indented code lines, or as a fenced php block, and assert on it with "// => value", "// Output: text", or "// throws ExceptionClass".',
                17,
            ],
        ]);
    }

    public function testProcessNodeDocumentedExamplesAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireExampleOnPublicApi/DocumentedExamples.php'], []);
    }

    public function testProcessNodeDisplayOnlyExampleDoesNotSatisfyTheRequirement(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireExampleOnPublicApi/InlineExampleOnly.php'], [
            [
                'Add an @example block to class InlineExampleOnly: it is declared public API with "@visibility public", so it must document at least one example doctest can run. Write the example as an "@example" tag followed by indented code lines, or as a fenced php block, and assert on it with "// => value", "// Output: text", or "// throws ExceptionClass".',
                14,
            ],
        ]);
    }

    public function testProcessNodeUntaggedDeclarationIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireExampleOnPublicApi/UntaggedApi.php'], []);
    }

    public function testProcessNodeNarrowedVisibilityIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireExampleOnPublicApi/ScopedApi.php'], []);
    }

    public function testProcessNodeClassInRestrictedTestNamespaceIsSkipped(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequireExampleOnPublicApi/TestClassInRestrictedNamespace.php'], []);
    }
}
