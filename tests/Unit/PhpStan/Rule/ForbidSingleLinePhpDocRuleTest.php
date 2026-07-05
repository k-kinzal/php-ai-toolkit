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
                'Single-line PHPDoc is prohibited: "/** Single-line class doc. */". Rewrite as a multi-line PHPDoc block: open with /** on its own line, write the description on the next line prefixed with " * ", and close with */ on its own line.',
                7,
            ],
            [
                'Single-line PHPDoc is prohibited: "/** Single-line constant doc. */". Rewrite as a multi-line PHPDoc block: open with /** on its own line, write the description on the next line prefixed with " * ", and close with */ on its own line.',
                10,
            ],
            [
                'Single-line PHPDoc is prohibited: "/** Single-line property doc. */". Rewrite as a multi-line PHPDoc block: open with /** on its own line, write the description on the next line prefixed with " * ", and close with */ on its own line.',
                13,
            ],
            [
                'Single-line PHPDoc is prohibited: "/** Single-line method doc. */". Rewrite as a multi-line PHPDoc block: open with /** on its own line, write the description on the next line prefixed with " * ", and close with */ on its own line.',
                16,
            ],
        ]);
    }

    public function testProcessNodeMultiLinePhpDocIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidSingleLinePhpDoc/WithMultiLineDoc.php'], []);
    }
}
