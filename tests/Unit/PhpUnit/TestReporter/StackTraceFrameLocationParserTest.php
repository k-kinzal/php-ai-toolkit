<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PhpAiToolkit\PhpUnit\TestReporter\StackTraceFrameLocationParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StackTraceFrameLocationParser::class)]
final class StackTraceFrameLocationParserTest extends TestCase
{
    public function testParseReadsEventStyleFrame(): void
    {
        $parser = new StackTraceFrameLocationParser();

        self::assertSame(['file' => '/tmp/FooTest.php', 'line' => 42], $parser->parse('/tmp/FooTest.php:42'));
    }

    public function testParseReadsPhpTraceStyleFrame(): void
    {
        $parser = new StackTraceFrameLocationParser();

        self::assertSame(['file' => '/tmp/FooTest.php', 'line' => 43], $parser->parse('#0 /tmp/FooTest.php(43): FooTest->testFoo()'));
    }

    public function testParseReturnsNullForNonLocationFrame(): void
    {
        $parser = new StackTraceFrameLocationParser();

        self::assertNull($parser->parse('#1 [internal function]'));
    }
}
