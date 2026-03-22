<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use PhpStanAiRules\Rule\ForbidNonDocCommentRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends RuleTestCase<ForbidNonDocCommentRule>
 */
#[CoversClass(ForbidNonDocCommentRule::class)]
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
        $this->analyse([__DIR__ . '/../../Fixture/ForbidNonDocComment/WithDoubleSlashComment.php'], [
            [
                'Non-PHPDoc comment is prohibited: "// This is a line comment". Only /** ... */ PHPDoc blocks are allowed. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                5,
            ],
            [
                'Non-PHPDoc comment is prohibited: "// trailing comment". Only /** ... */ PHPDoc blocks are allowed. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                8,
            ],
        ]);
    }

    public function testProcessNodeBlockCommentIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/ForbidNonDocComment/WithBlockComment.php'], [
            [
                'Non-PHPDoc comment is prohibited: "/* This is a block comment */". Only /** ... */ PHPDoc blocks are allowed. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                5,
            ],
            [
                'Non-PHPDoc comment is prohibited: "/* inline block */". Only /** ... */ PHPDoc blocks are allowed. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                8,
            ],
        ]);
    }

    public function testProcessNodeHashCommentIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/ForbidNonDocComment/WithHashComment.php'], [
            [
                'Non-PHPDoc comment is prohibited: "# This is a hash comment". Only /** ... */ PHPDoc blocks are allowed. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                5,
            ],
        ]);
    }

    public function testProcessNodePhpDocIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/ForbidNonDocComment/WithPhpDocOnly.php'], []);
    }

    public function testProcessNodePhpstanIgnoreAndInfectionIgnoreAreSkipped(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/ForbidNonDocComment/WithPhpstanIgnore.php'], [
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
}
