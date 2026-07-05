<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\AnonymousClassDetector;
use PhpAiToolkit\PhpStan\Rule\CommentTextFormatter;
use PhpAiToolkit\PhpStan\Rule\ForbidSingleLinePhpDocRule;
use PhpAiToolkit\PhpStan\Rule\SingleLinePhpDocDetector;
use PhpAiToolkit\PhpStan\Rule\SingleLinePhpDocErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\SingleLinePhpDocErrorCollector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<ForbidSingleLinePhpDocRule>
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
