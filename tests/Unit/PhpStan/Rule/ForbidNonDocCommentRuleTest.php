<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\ForbidNonDocCommentRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<ForbidNonDocCommentRule>
 */
#[CoversClass(ForbidNonDocCommentRule::class)]
#[Medium]
final class ForbidNonDocCommentRuleTest extends RuleTestCase
{
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
                'Non-PHPDoc comment is prohibited: "// This is a line comment". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks or array literals. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                5,
            ],
            [
                'Non-PHPDoc comment is prohibited: "// trailing comment". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks or array literals. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                8,
            ],
        ]);
    }

    public function testProcessNodeBlockCommentIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithBlockComment.php'], [
            [
                'Non-PHPDoc comment is prohibited: "/* This is a block comment */". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks or array literals. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                5,
            ],
            [
                'Non-PHPDoc comment is prohibited: "/* inline block */". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks or array literals. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                8,
            ],
        ]);
    }

    public function testProcessNodeHashCommentIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithHashComment.php'], [
            [
                'Non-PHPDoc comment is prohibited: "# This is a hash comment". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks or array literals. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
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
                'Non-PHPDoc comment is prohibited: "// This comment is inside array access, not an array literal.". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks or array literals. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                8,
            ],
        ]);
    }

    public function testProcessNodeBlockAndHashCommentsInsideCatchBodyAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithCatchNonLineComment.php'], [
            [
                'Non-PHPDoc comment is prohibited: "/* Block comments are still prohibited inside catch blocks. */". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks or array literals. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                10,
            ],
            [
                'Non-PHPDoc comment is prohibited: "# Hash comments are still prohibited inside catch blocks.". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks or array literals. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                11,
            ],
        ]);
    }

    public function testProcessNodeBlockAndHashCommentsInsideArrayLiteralAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithArrayNonLineComment.php'], [
            [
                'Non-PHPDoc comment is prohibited: "/* Block comments are still prohibited inside arrays. */". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks or array literals. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                8,
            ],
            [
                'Non-PHPDoc comment is prohibited: "# Hash comments are still prohibited inside arrays.". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks or array literals. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                9,
            ],
        ]);
    }

    public function testProcessNodeDoubleSlashCommentAfterCatchBodyIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidNonDocComment/WithCatchBoundaryLineComment.php'], [
            [
                'Non-PHPDoc comment is prohibited: "// This comment is outside the catch body.". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks or array literals. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                11,
            ],
        ]);
    }
}
