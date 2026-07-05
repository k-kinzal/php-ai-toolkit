<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\CommentTextFormatter;
use PhpAiToolkit\PhpStan\Rule\FileTokenParser;
use PhpAiToolkit\PhpStan\Rule\ForbiddenCommentPattern;
use PhpAiToolkit\PhpStan\Rule\ForbidNonDocCommentRule;
use PhpAiToolkit\PhpStan\Rule\NonDocCommentErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\NonDocCommentTokenAnalyzer;
use PhpAiToolkit\PhpStan\Support\NonDocCommentArrayContext;
use PhpAiToolkit\PhpStan\Support\NonDocCommentCatchContext;
use PhpAiToolkit\PhpStan\Support\NonDocCommentContext;
use PhpAiToolkit\PhpStan\Support\NonDocCommentTokenClassifier;
use PhpAiToolkit\PhpStan\Support\ShortArrayOpeningPolicy;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<ForbidNonDocCommentRule>
 */
#[CoversClass(ForbidNonDocCommentRule::class)]
#[UsesClass(CommentTextFormatter::class)]
#[UsesClass(FileTokenParser::class)]
#[UsesClass(ForbiddenCommentPattern::class)]
#[UsesClass(NonDocCommentArrayContext::class)]
#[UsesClass(NonDocCommentCatchContext::class)]
#[UsesClass(NonDocCommentContext::class)]
#[UsesClass(NonDocCommentErrorBuilder::class)]
#[UsesClass(NonDocCommentTokenAnalyzer::class)]
#[UsesClass(NonDocCommentTokenClassifier::class)]
#[UsesClass(ShortArrayOpeningPolicy::class)]
#[Medium]
final class ForbidNonDocCommentRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidNonDocCommentRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PHPStan\Node\FileNode::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeDoubleSlashCommentIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithDoubleSlashComment.php'], [
            [
                'Remove comment "// This is a line comment" or convert it to /** ... */ PHPDoc. Only // comments inside catch blocks or array literals are allowed.',
                5,
            ],
            [
                'Remove comment "// trailing comment" or convert it to /** ... */ PHPDoc. Only // comments inside catch blocks or array literals are allowed.',
                8,
            ],
        ]);
    }

    public function testProcessNodeBlockCommentIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithBlockComment.php'], [
            [
                'Remove comment "/* This is a block comment */" or convert it to /** ... */ PHPDoc. Only // comments inside catch blocks or array literals are allowed.',
                5,
            ],
            [
                'Remove comment "/* inline block */" or convert it to /** ... */ PHPDoc. Only // comments inside catch blocks or array literals are allowed.',
                8,
            ],
        ]);
    }

    public function testProcessNodeHashCommentIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithHashComment.php'], [
            [
                'Remove comment "# This is a hash comment" or convert it to /** ... */ PHPDoc. Only // comments inside catch blocks or array literals are allowed.',
                5,
            ],
        ]);
    }

    public function testProcessNodePhpDocIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithPhpDocOnly.php'], []);
    }

    public function testProcessNodePhpstanIgnoreAndInfectionIgnoreAreSkipped(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithPhpstanIgnore.php'], [
            [
                'No error with identifier argument.type is reported on line 5.',
                5,
            ],
            [
                'No error to ignore is reported on line 8.',
                8,
            ],
        ]);
    }

    public function testProcessNodeDoubleSlashCommentInsideCatchBodyIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithCatchLineComment.php'], []);
    }

    public function testProcessNodeDoubleSlashCommentInsideArrayLiteralIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithArrayLineComment.php'], []);
    }

    public function testProcessNodeDoubleSlashCommentInsideLongArrayLiteralIsNotReported(): void
    {
        $file = sys_get_temp_dir() . '/forbid-non-doc-comment-' . bin2hex(random_bytes(4)) . '.php';
        self::assertNotFalse(file_put_contents($file, <<<'PHP'
<?php

declare(strict_types=1);

function fixtureLongArrayLineComment(): array
{
    return array(
        // This long array syntax comment is allowed.
        'value',
    );
}
PHP));

        try {
            $this->analyse([$file], []);
        } finally {
            unlink($file);
        }
    }

    public function testProcessNodeDoubleSlashCommentInsideArrayAccessIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithArrayAccessLineComment.php'], [
            [
                'Remove comment "// This comment is inside array access, not an array literal." or convert it to /** ... */ PHPDoc. Only // comments inside catch blocks or array literals are allowed.',
                8,
            ],
        ]);
    }

    public function testProcessNodeBlockAndHashCommentsInsideCatchBodyAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithCatchNonLineComment.php'], [
            [
                'Remove comment "/* Block comments are still prohibited inside catch blocks. */" or convert it to /** ... */ PHPDoc. Only // comments inside catch blocks or array literals are allowed.',
                10,
            ],
            [
                'Remove comment "# Hash comments are still prohibited inside catch blocks." or convert it to /** ... */ PHPDoc. Only // comments inside catch blocks or array literals are allowed.',
                11,
            ],
        ]);
    }

    public function testProcessNodeBlockAndHashCommentsInsideArrayLiteralAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithArrayNonLineComment.php'], [
            [
                'Remove comment "/* Block comments are still prohibited inside arrays. */" or convert it to /** ... */ PHPDoc. Only // comments inside catch blocks or array literals are allowed.',
                8,
            ],
            [
                'Remove comment "# Hash comments are still prohibited inside arrays." or convert it to /** ... */ PHPDoc. Only // comments inside catch blocks or array literals are allowed.',
                9,
            ],
        ]);
    }

    public function testProcessNodeDoubleSlashCommentAfterCatchBodyIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithCatchBoundaryLineComment.php'], [
            [
                'Remove comment "// This comment is outside the catch body." or convert it to /** ... */ PHPDoc. Only // comments inside catch blocks or array literals are allowed.',
                11,
            ],
        ]);
    }
}
