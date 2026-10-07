<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Cli\DocGenHelpText;

/**
 * @covers \Toolkit\DocGen\Cli\DocGenHelpText
 */
#[CoversClass(DocGenHelpText::class)]
final class DocGenHelpTextTest extends TestCase
{
    public function testTextJoinsThePurposeTheExamplesAndTheExitCodes(): void
    {
        $help = new DocGenHelpText();

        self::assertSame($help->purpose() . "\n\n" . $help->examples() . "\n\n" . $help->exitCodes(), $help->text());
    }

    public function testTextNamesNoConfigurationFile(): void
    {
        self::assertStringNotContainsString('--config', (new DocGenHelpText())->text());
    }

    public function testPurposeStatesWhatDocGenDoes(): void
    {
        $purpose = (new DocGenHelpText())->purpose();

        self::assertStringStartsWith('Generates a static HTML documentation site', $purpose);
        self::assertStringContainsString('may be repeated', $purpose);
    }

    public function testExamplesShowCommandLines(): void
    {
        $examples = (new DocGenHelpText())->examples();

        self::assertStringStartsWith('Examples:', $examples);
        self::assertStringContainsString('docgen --diff=main', $examples);
    }

    public function testExitCodesNameEveryOutcome(): void
    {
        $codes = (new DocGenHelpText())->exitCodes();

        self::assertStringContainsString('0  documentation generated', $codes);
        self::assertStringContainsString('2  invalid command line', $codes);
    }
}
