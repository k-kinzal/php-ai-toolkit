<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\CommentTextFormatter;
use PhpAiToolkit\PhpStan\Rule\FileTokenParser;
use PhpAiToolkit\PhpStan\Rule\ForbiddenCommentErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\ForbiddenCommentPattern;
use PhpAiToolkit\PhpStan\Rule\ForbiddenCommentRule;
use PhpAiToolkit\PhpStan\Rule\ForbiddenCommentTokenAnalyzer;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<ForbiddenCommentRule>
 */
#[CoversClass(ForbiddenCommentRule::class)]
#[UsesClass(CommentTextFormatter::class)]
#[UsesClass(FileTokenParser::class)]
#[UsesClass(ForbiddenCommentErrorBuilder::class)]
#[UsesClass(ForbiddenCommentPattern::class)]
#[UsesClass(ForbiddenCommentTokenAnalyzer::class)]
#[Medium]
final class ForbiddenCommentRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbiddenCommentRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PHPStan\Node\FileNode::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodePhpstanIgnoreNextLineIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbiddenComment/PhpstanIgnoreNextLine.php'], [
            [
                'phpstan-ignore comments are prohibited: "/** @phpstan-ignore-next-line */". Remove this comment and re-run PHPStan to reveal the actual error it was suppressing, then fix the root cause. If the error is a false positive, ask a human operator to add an ignoreErrors entry in phpstan.neon with the error\'s identifier.',
                5,
            ],
            [
                'No error to ignore is reported on line 6.',
                6,
            ],
        ]);
    }

    public function testProcessNodePhpstanIgnoreBareIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbiddenComment/PhpstanIgnoreBare.php'], [
            [
                'No error with identifier argument.type is reported on line 5.',
                5,
            ],
            [
                'phpstan-ignore comments are prohibited: "// @phpstan-ignore argument.type". Remove this comment and re-run PHPStan to reveal the actual error it was suppressing, then fix the root cause. If the error is a false positive, ask a human operator to add an ignoreErrors entry in phpstan.neon with the error\'s identifier.',
                5,
            ],
        ]);
    }

    public function testProcessNodeInfectionIgnoreAllIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbiddenComment/InfectionIgnoreAll.php'], [
            [
                'infection-ignore-all comments are prohibited: "/** @infection-ignore-all */". Remove this comment and run mutation testing to identify surviving mutants, then strengthen assertions or add test cases to kill them. If the code is genuinely untestable, restructure it to improve testability.',
                5,
            ],
        ]);
    }

    public function testProcessNodeNormalCommentsAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbiddenComment/NormalComments.php'], []);
    }
}
