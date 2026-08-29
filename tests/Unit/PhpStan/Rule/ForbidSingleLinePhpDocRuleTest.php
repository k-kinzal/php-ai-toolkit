<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\ForbidSingleLinePhpDocRule;
use Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocDetector;
use Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocErrorBuilder;
use Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocErrorCollector;
use Toolkit\PhpStan\Rule\Shared\AnonymousClassDetector;
use Toolkit\PhpStan\Rule\Shared\CommentTextFormatter;

/**
 * @extends RuleTestCase<ForbidSingleLinePhpDocRule>
 * @covers \Toolkit\PhpStan\Rule\ForbidSingleLinePhpDocRule
 * @uses \Toolkit\PhpStan\Rule\Shared\AnonymousClassDetector
 * @uses \Toolkit\PhpStan\Rule\Shared\CommentTextFormatter
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocDetector
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocErrorCollector
 * @medium
 */
#[CoversClass(ForbidSingleLinePhpDocRule::class)]
#[UsesClass(AnonymousClassDetector::class)]
#[UsesClass(CommentTextFormatter::class)]
#[UsesClass(SingleLinePhpDocDetector::class)]
#[UsesClass(SingleLinePhpDocErrorBuilder::class)]
#[UsesClass(SingleLinePhpDocErrorCollector::class)]
#[Medium]
final class ForbidSingleLinePhpDocRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidSingleLinePhpDocRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassLike::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeSingleLinePhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidSingleLinePhpDoc/WithSingleLineDoc.php'], [
            [
                'Rewrite PHPDoc "/** Single-line class doc. */" as a multi-line block with /** and */ on their own lines.',
                7,
            ],
            [
                'Rewrite PHPDoc "/** Single-line constant doc. */" as a multi-line block with /** and */ on their own lines.',
                10,
            ],
            [
                'Rewrite PHPDoc "/** Single-line property doc. */" as a multi-line block with /** and */ on their own lines.',
                13,
            ],
            [
                'Rewrite PHPDoc "/** Single-line method doc. */" as a multi-line block with /** and */ on their own lines.',
                16,
            ],
        ]);
    }

    public function testProcessNodeMultiLinePhpDocIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidSingleLinePhpDoc/WithMultiLineDoc.php'], []);
    }
}
