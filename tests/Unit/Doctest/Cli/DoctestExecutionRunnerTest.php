<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Cli;

use PhpAiToolkit\Doctest\Cli\DoctestConfigPathResolver;
use PhpAiToolkit\Doctest\Cli\DoctestExecutionRunner;
use PhpAiToolkit\Doctest\Cli\DoctestOutputWriter;
use PhpAiToolkit\Doctest\Cli\DoctestReporterOverride;
use PhpAiToolkit\Doctest\Config\ConfigLoader;
use PhpAiToolkit\Doctest\Execution\SuiteRunner;
use PhpAiToolkit\Doctest\Reporting\ReporterFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;

#[CoversClass(DoctestExecutionRunner::class)]
#[Medium]
final class DoctestExecutionRunnerTest extends TestCase
{
    public function testRunReportsAPassingProjectAndExitsZero(): void
    {
        $output = '';
        $writer = new DoctestOutputWriter(static function (string $message) use (&$output): void {
            $output .= $message;
        });
        $runner = new DoctestExecutionRunner(__DIR__ . '/../../../Fixture/Doctest/project', new ConfigLoader(), new SuiteRunner(), new ReporterFactory(), $writer);

        self::assertSame(0, $runner->run('doctest.yaml', null, null));
        self::assertStringStartsWith("DOCTEST_PASSED\n", $output);
    }

    public function testRunReportsAFailingProjectAndExitsOne(): void
    {
        $output = '';
        $writer = new DoctestOutputWriter(static function (string $message) use (&$output): void {
            $output .= $message;
        });
        $runner = new DoctestExecutionRunner(__DIR__ . '/../../../Fixture/Doctest/failing', new ConfigLoader(), new SuiteRunner(), new ReporterFactory(), $writer);

        self::assertSame(1, $runner->run('doctest.yaml', 'text', null));
        self::assertStringStartsWith('Doctest found 1 failing examples.', $output);
    }

    public function testRunReportsAConfigurationErrorOnStandardErrorAndExitsTwo(): void
    {
        $errors = '';
        $writer = new DoctestOutputWriter(null, static function (string $message) use (&$errors): void {
            $errors .= $message;
        });
        $runner = new DoctestExecutionRunner('/does/not/exist', new ConfigLoader(), new SuiteRunner(), new ReporterFactory(), $writer);

        self::assertSame(2, $runner->run('doctest.yaml', null, null));
        self::assertStringStartsWith('Doctest error: Doctest config not found', $errors);
    }

    public function testListPrintsTheIdentifierAndLocationOfEverySelectedExample(): void
    {
        $output = '';
        $writer = new DoctestOutputWriter(static function (string $message) use (&$output): void {
            $output .= $message;
        });
        $runner = new DoctestExecutionRunner(__DIR__ . '/../../../Fixture/Doctest/project', new ConfigLoader(), new SuiteRunner(), new ReporterFactory(), $writer);

        self::assertSame(0, $runner->list('doctest.yaml', 'divide'));
        self::assertSame("Tests\Fixture\Doctest\Project\Calculator::divide()#1\tsrc/Calculator.php:40\n", $output);
    }

    public function testListReportsAConfigurationErrorAndExitsTwo(): void
    {
        $errors = '';
        $writer = new DoctestOutputWriter(null, static function (string $message) use (&$errors): void {
            $errors .= $message;
        });
        $runner = new DoctestExecutionRunner('/does/not/exist', new ConfigLoader(), new SuiteRunner(), new ReporterFactory(), $writer);

        self::assertSame(2, $runner->list('doctest.yaml', null));
        self::assertStringStartsWith('Doctest error: Doctest config not found', $errors);
    }

    public function testConfigLoadsTheFileNamedRelativeToTheWorkingDirectory(): void
    {
        $runner = new DoctestExecutionRunner(
            __DIR__ . '/../../../Fixture/Doctest/project',
            new ConfigLoader(),
            new SuiteRunner(),
            new ReporterFactory(),
            new DoctestOutputWriter(),
            new DoctestConfigPathResolver(),
            new DoctestReporterOverride(),
        );

        self::assertSame(['src'], $runner->config('doctest.yaml')->paths);
    }
}
