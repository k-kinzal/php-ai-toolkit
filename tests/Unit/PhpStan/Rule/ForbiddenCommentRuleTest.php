<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PHPStan\Analyser\Error;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\ForbiddenCommentRule;
use Toolkit\PhpStan\Rule\Shared\CommentTextFormatter;
use Toolkit\PhpStan\Rule\Shared\FileTokenParser;
use Toolkit\PhpStan\Rule\Shared\ForbiddenCommentErrorBuilder;
use Toolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern;
use Toolkit\PhpStan\Rule\Shared\ForbiddenCommentTokenAnalyzer;

/**
 * @extends RuleTestCase<ForbiddenCommentRule>
 * @covers \Toolkit\PhpStan\Rule\ForbiddenCommentRule
 * @uses \Toolkit\PhpStan\Rule\Shared\CommentTextFormatter
 * @uses \Toolkit\PhpStan\Rule\Shared\FileTokenParser
 * @uses \Toolkit\PhpStan\Rule\Shared\ForbiddenCommentErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern
 * @uses \Toolkit\PhpStan\Rule\Shared\ForbiddenCommentTokenAnalyzer
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
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../../../Fixture/ForbiddenComment/PhpstanIgnoreBare.php']);
        $messages = array_map(static fn (Error $error): string => sprintf('%d: %s', (int) $error->getLine(), $error->getMessage()), $errors);
        sort($messages);

        self::assertSame([
            '5: No error with identifier argument.type is reported on line 5.',
            '5: Remove phpstan-ignore comment "// @phpstan-ignore argument.type". Re-run PHPStan and fix the revealed error. AI agents must not edit ignoreErrors; ask a human operator only when suppression is genuinely justified.',
        ], $messages);
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
