<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Reporting;

use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\Target;
use PhpAiToolkit\Doctest\Execution\RunFailure;
use PhpAiToolkit\Doctest\Execution\RunResult;
use PhpAiToolkit\Doctest\Reporting\AiFailureFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AiFailureFormatter::class)]
#[UsesClass(RunResult::class)]
#[UsesClass(RunFailure::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(DocExample::class)]
final class AiFailureFormatterTest extends TestCase
{
    public function testFormatNamesTheExampleAndTheCommandThatRerunsIt(): void
    {
        $target = new Target(Target::METHOD, '/app/src/Ledger.php', '/** */', 'append', 10, 'App', 'Ledger', [], 'src/Ledger.php');
        $example = new Example($target, new DocExample(null, 'append()', 'tag', 0), 13);
        $result = new RunResult($example, [new RunFailure('append()', 1, 'Values differ.', '1', '2')]);

        $block = (new AiFailureFormatter())->format(1, $result);

        self::assertStringContainsString("1. src/Ledger.php:13 [doctest]\n", $block);
        self::assertStringContainsString("   example: App\Ledger::append()#1\n", $block);
        self::assertStringContainsString("   rerun: vendor/bin/doctest --filter='App\Ledger::append()#1'\n", $block);
        self::assertStringContainsString("   - line 1: Values differ.\n", $block);
    }

    public function testAssertionShowsTheComparedValuesOnlyWhenThereAreSome(): void
    {
        $formatter = new AiFailureFormatter();

        self::assertSame(
            "   - line 3: Values differ.\n     code: append()\n     expected: 1\n     actual: 2\n",
            $formatter->assertion(new RunFailure('append()', 3, 'Values differ.', '1', '2')),
        );
        self::assertSame(
            "   - line 3: Statement raised RuntimeException: bad\n     code: append()\n",
            $formatter->assertion(new RunFailure('append()', 3, 'Statement raised RuntimeException: bad')),
        );
    }
}
