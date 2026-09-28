<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Reporting\AiViolationAction;
use Toolkit\DocGuard\Reporting\AiViolationFormatter;

/**
 * @covers \Toolkit\DocGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\DocGuard\Reporting\AiViolationAction
 * @uses \Toolkit\DocGuard\Analysis\Violation
 */
#[CoversClass(AiViolationFormatter::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(Violation::class)]
final class AiViolationFormatterTest extends TestCase
{
    public function testFormatIncludesLocationHeadingsMessageAndAction(): void
    {
        $output = (new AiViolationFormatter())->format(2, new Violation('README.md', 9, 'renamed_heading', '## Usage', '## Getting Started', 'Renamed.'));

        self::assertStringStartsWith("2. README.md:9 [renamed_heading]\n   expected: ## Usage\n   actual: ## Getting Started\n   message: Renamed.\n   action: Restore the declared heading text", $output);
    }

    public function testFormatOmitsMissingLineAndHeadings(): void
    {
        $output = (new AiViolationFormatter())->format(1, new Violation('docs/x.md', null, 'undeclared_document', null, null, 'Added.'));

        self::assertStringStartsWith("1. docs/x.md [undeclared_document]\n   message: Added.\n", $output);
        self::assertStringNotContainsString('expected:', $output);
    }
}
