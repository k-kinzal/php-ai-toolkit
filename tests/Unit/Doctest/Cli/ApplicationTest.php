<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Cli;

use PhpAiToolkit\Doctest\Cli\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;

#[CoversClass(Application::class)]
#[Medium]
final class ApplicationTest extends TestCase
{
    public function testRunPrintsHelpAndExitsZero(): void
    {
        $output = '';
        $application = new Application(__DIR__, null, null, null, static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $application->run(['doctest', '--help']));
        self::assertStringStartsWith('doctest runs the examples written in PHPDoc blocks.', $output);
    }

    public function testRunPrintsTheVersionAndExitsZero(): void
    {
        $output = '';
        $application = new Application(__DIR__, null, null, null, static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $application->run(['doctest', '--version']));
        self::assertSame("doctest 1.0.0\n", $output);
    }

    public function testRunReportsAnUnknownOptionAndExitsTwo(): void
    {
        $errors = '';
        $application = new Application(__DIR__, null, null, null, null, static function (string $message) use (&$errors): void {
            $errors .= $message;
        });

        self::assertSame(2, $application->run(['doctest', '--nope']));
        self::assertSame("Doctest error: Unknown option: --nope\n", $errors);
    }

    public function testRunListsExamplesWithoutRunningThem(): void
    {
        $output = '';
        $application = new Application(__DIR__ . '/../../../Fixture/Doctest/project', null, null, null, static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $application->run(['doctest', '--list', '--filter=divide']));
        self::assertStringContainsString('Calculator::divide()#1', $output);
    }

    public function testRunExecutesTheSelectedExamples(): void
    {
        $output = '';
        $application = new Application(__DIR__ . '/../../../Fixture/Doctest/project', null, null, null, static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $application->run(['doctest', '--reporter=text']));
        self::assertStringStartsWith('Doctest passed.', $output);
    }
}
