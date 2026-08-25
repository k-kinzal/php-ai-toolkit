<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse;

use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function str_repeat;

use Toolkit\DocGen\Analysis\Parse\ExprTextPrinter;

/**
 * @covers \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 */
#[CoversClass(ExprTextPrinter::class)]
final class ExprTextPrinterTest extends TestCase
{
    public function testPrintReturnsNullForMissingExpression(): void
    {
        self::assertNull((new ExprTextPrinter())->print(null));
    }

    public function testPrintPrintsShortExpressionText(): void
    {
        self::assertSame("'draft'", (new ExprTextPrinter())->print(new String_('draft')));
    }

    public function testPrintKeepsTextAtMaximumLength(): void
    {
        $text = (new ExprTextPrinter())->print(new String_(str_repeat('a', 78)));

        self::assertSame("'" . str_repeat('a', 78) . "'", $text);
    }

    public function testPrintTruncatesLongExpressionText(): void
    {
        $text = (new ExprTextPrinter())->print(new String_(str_repeat('a', 100)));

        self::assertSame("'" . str_repeat('a', 78) . '…', $text);
    }
}
