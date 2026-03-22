<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PhpStanAiRules\Rule\ForbidSingleLinePhpDocRule;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends RuleTestCase<ForbidSingleLinePhpDocRule>
 */
#[CoversClass(ForbidSingleLinePhpDocRule::class)]
final class ForbidSingleLinePhpDocRuleTest extends RuleTestCase
{
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
        $this->analyse([__DIR__ . '/../../Fixture/ForbidSingleLinePhpDoc/WithSingleLineDoc.php'], [
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
        $this->analyse([__DIR__ . '/../../Fixture/ForbidSingleLinePhpDoc/WithMultiLineDoc.php'], []);
    }
}
