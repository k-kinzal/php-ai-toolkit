<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use DecisionsLint\Cli\ClosureOutput;
use DecisionsLint\Diagnostic\LintException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \DecisionsLint\Cli\ClosureOutput
 * @uses \DecisionsLint\Diagnostic\LintException
 */
#[CoversClass(ClosureOutput::class)]
#[UsesClass(LintException::class)]
final class ClosureOutputTest extends TestCase
{
    public function testGetErrorOutputIsTheOutputItselfWithoutASeparateSink(): void
    {
        $written = '';
        $output = new ClosureOutput(static function (string $text) use (&$written): void {
            $written .= $text;
        });
        self::assertSame($output, $output->getErrorOutput());
        $output->getErrorOutput()->writeln('oops');
        self::assertSame("oops\n", $written);
    }

    public function testSetErrorOutputRedirectsErrors(): void
    {
        $errors = new BufferedOutput();
        $output = new ClosureOutput(static function (string $text): void {
        });
        $output->setErrorOutput($errors);
        $output->getErrorOutput()->write('bad');
        self::assertSame('bad', $errors->fetch());
    }

    public function testSectionIsRefused(): void
    {
        $this->expectException(LintException::class);
        (new ClosureOutput(static function (string $text): void {
        }))->section();
    }

    public function testSetVerbosityPropagatesToTheErrorOutput(): void
    {
        $errors = new BufferedOutput();
        $output = new ClosureOutput(static function (string $text): void {
        }, $errors);
        $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        self::assertSame(OutputInterface::VERBOSITY_QUIET, $errors->getVerbosity());
    }

    public function testSetDecoratedPropagatesToTheErrorOutput(): void
    {
        $errors = new BufferedOutput();
        $output = new ClosureOutput(static function (string $text): void {
        }, $errors);
        $output->setDecorated(true);
        self::assertTrue($errors->isDecorated());
    }

    public function testSetFormatterPropagatesToTheErrorOutput(): void
    {
        $errors = new BufferedOutput();
        $output = new ClosureOutput(static function (string $text): void {
        }, $errors);
        $formatter = new OutputFormatter();
        $output->setFormatter($formatter);
        self::assertSame($formatter, $errors->getFormatter());
    }
}
