<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use PhpAiToolkit\Doctest\Execution\DiagnosticLog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DiagnosticLog::class)]
final class DiagnosticLogTest extends TestCase
{
    public function testRecordCollectsReportedDiagnostics(): void
    {
        $log = new DiagnosticLog();

        self::assertTrue($log->record(E_WARNING, 'Undefined variable $sum'));
        self::assertTrue($log->raised());
        self::assertSame('Undefined variable $sum', $log->summary());
    }

    public function testRecordKeepsRecordingAtTheLevelTheLogWasCreatedWith(): void
    {
        $log = new DiagnosticLog();

        self::assertTrue($log->record(E_NOTICE, 'still reported'));
        self::assertSame('still reported', $log->summary());
    }

    public function testSuppressedTellsTheAtOperatorFromANarrowedReportingLevel(): void
    {
        $log = new DiagnosticLog(E_ALL);

        self::assertTrue($log->suppressed(E_WARNING, E_ERROR | E_PARSE));
        self::assertFalse($log->suppressed(E_WARNING, E_ALL));
        self::assertFalse($log->suppressed(E_WARNING, E_ALL & ~E_NOTICE));
    }

    public function testRaisedIsFalseUntilSomethingIsRecorded(): void
    {
        self::assertFalse((new DiagnosticLog())->raised());
    }

    public function testSummaryJoinsEveryRecordedDiagnostic(): void
    {
        $log = new DiagnosticLog();
        $log->record(E_WARNING, 'first');
        $log->record(E_NOTICE, 'second');

        self::assertSame('first; second', $log->summary());
    }

    public function testHandlerFeedsTheLog(): void
    {
        $log = new DiagnosticLog();
        $handler = $log->handler();

        self::assertTrue($handler(E_WARNING, 'from the handler'));
        self::assertSame('from the handler', $log->summary());
    }
}
