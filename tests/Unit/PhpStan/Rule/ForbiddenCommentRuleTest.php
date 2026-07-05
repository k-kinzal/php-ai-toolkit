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
                'Remove phpstan-ignore comment "/** @phpstan-ignore-next-line */". Re-run PHPStan and fix the revealed error. AI agents must not edit ignoreErrors; ask a human operator only when suppression is genuinely justified.',
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
                'Remove phpstan-ignore comment "// @phpstan-ignore argument.type". Re-run PHPStan and fix the revealed error. AI agents must not edit ignoreErrors; ask a human operator only when suppression is genuinely justified.',
                5,
            ],
        ]);
    }

    public function testProcessNodeInfectionIgnoreAllIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbiddenComment/InfectionIgnoreAll.php'], [
            [
                'Remove infection-ignore-all comment "/** @infection-ignore-all */". Run mutation testing and strengthen assertions or add focused tests. Ask a human operator only when an exception is genuinely justified.',
                5,
            ],
        ]);
    }

    public function testProcessNodeNormalCommentsAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbiddenComment/NormalComments.php'], []);
    }
}
