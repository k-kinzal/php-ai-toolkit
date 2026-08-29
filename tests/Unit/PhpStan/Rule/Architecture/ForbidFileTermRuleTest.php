<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Architecture;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermErrorBuilder;
use Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermInspector;
use Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermRestrictions;
use Toolkit\PhpStan\Rule\Architecture\ForbidFileTermRule;
use Toolkit\PhpStan\Rule\Shared\LineOrderedErrors;
use Toolkit\PhpStan\Rule\Shared\Path\RulePathMatcher;
use Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer;

/**
 * @extends RuleTestCase<ForbidFileTermRule>
 * @covers \Toolkit\PhpStan\Rule\Architecture\ForbidFileTermRule
 * @uses \Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermInspector
 * @uses \Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermRestrictions
 * @uses \Toolkit\PhpStan\Rule\Shared\LineOrderedErrors
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\RulePathMatcher
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer
 * @medium
 */
#[CoversClass(ForbidFileTermRule::class)]
#[UsesClass(ForbiddenFileTermErrorBuilder::class)]
#[UsesClass(ForbiddenFileTermInspector::class)]
#[UsesClass(ForbiddenFileTermRestrictions::class)]
#[UsesClass(LineOrderedErrors::class)]
#[UsesClass(RulePathMatcher::class)]
#[UsesClass(RulePathNormalizer::class)]
#[Medium]
final class ForbidFileTermRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidFileTermRule([
            'tests/Fixture/ForbidFileTerm/*' => ['mysql', 'MYSQL', 'postgres', 'sqlite', ''],
        ]);
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PHPStan\Node\FileNode::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeReportsTermsInCommentsStringsAndIdentifiersWithoutCaseSensitivity(): void
    {
        $path = 'tests/Fixture/ForbidFileTerm/*';
        $message = static fn (string $term): string => sprintf(
            'Forbidden term "%s" appears in a file matched by path "%s"; this is a design error because the concept does not belong in this layer. Redesign the responsibility boundary and move the concept and its behavior to the appropriate layer. Renaming, abbreviating, or deleting only the term is not a fix.',
            $term,
            $path
        );
        $this->analyse([__DIR__ . '/../../../../Fixture/ForbidFileTerm/BackendLeak.php'], [
            [$message('postgres'), 8],
            [$message('mysql'), 12],
            [$message('sqlite'), 14],
        ]);
    }

    public function testProcessNodeAllowsARestrictedFileWithoutForbiddenTerms(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/ForbidFileTerm/BackendNeutral.php'], []);
    }

}
