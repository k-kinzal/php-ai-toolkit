<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use Guard\Cli\ClosureOutput;
use Guard\Diagnostic\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Guard\Cli\ClosureOutput
 * @uses \Guard\Diagnostic\PolicyException
 */
#[CoversClass(ClosureOutput::class)]
#[UsesClass(PolicyException::class)]
final class ClosureOutputTest extends TestCase
{
    public function testWriteHandsEveryMessageToTheSink(): void
    {
        $written = '';
        $output = new ClosureOutput(static function (string $message) use (&$written): void {
            $written .= $message;
        });

        $output->write('one');
        $output->writeln('two');

        self::assertSame("onetwo\n", $written);
    }

    public function testGetErrorOutputIsTheOutputItselfWithoutAnErrorOutput(): void
    {
        $output = new ClosureOutput(static function (): void {
        });

        self::assertSame($output, $output->getErrorOutput());
    }

    public function testGetErrorOutputIsTheErrorOutputItWasGiven(): void
    {
        $errors = '';
        $errorOutput = new ClosureOutput(static function (string $message) use (&$errors): void {
            $errors .= $message;
        });
        $output = new ClosureOutput(static function (): void {
        }, $errorOutput);

        $output->getErrorOutput()->writeln('failed');

        self::assertSame($errorOutput, $output->getErrorOutput());
        self::assertSame("failed\n", $errors);
    }

    public function testSetErrorOutputReplacesTheErrorOutput(): void
    {
        $output = new ClosureOutput(static function (): void {
        });
        $errorOutput = new ClosureOutput(static function (): void {
        });

        $output->setErrorOutput($errorOutput);

        self::assertSame($errorOutput, $output->getErrorOutput());
    }

    public function testSetVerbosityReachesTheErrorOutput(): void
    {
        $errorOutput = new ClosureOutput(static function (): void {
        });
        $output = new ClosureOutput(static function (): void {
        }, $errorOutput);

        $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);

        self::assertSame(OutputInterface::VERBOSITY_QUIET, $output->getVerbosity());
        self::assertSame(OutputInterface::VERBOSITY_QUIET, $errorOutput->getVerbosity());
    }

    public function testSetVerbosityOfAnOutputWithoutErrorOutputAppliesToItself(): void
    {
        $output = new ClosureOutput(static function (): void {
        });

        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        self::assertSame(OutputInterface::VERBOSITY_VERBOSE, $output->getVerbosity());
    }

    public function testSetDecoratedReachesTheErrorOutput(): void
    {
        $errorOutput = new ClosureOutput(static function (): void {
        });
        $output = new ClosureOutput(static function (): void {
        }, $errorOutput);

        $output->setDecorated(true);

        self::assertTrue($output->isDecorated());
        self::assertTrue($errorOutput->isDecorated());
    }

    public function testSetDecoratedOfAnOutputWithoutErrorOutputAppliesToItself(): void
    {
        $output = new ClosureOutput(static function (): void {
        });

        $output->setDecorated(true);

        self::assertTrue($output->isDecorated());
    }

    public function testSetFormatterReachesTheErrorOutput(): void
    {
        $errorOutput = new ClosureOutput(static function (): void {
        });
        $output = new ClosureOutput(static function (): void {
        }, $errorOutput);
        $formatter = new OutputFormatter();

        $output->setFormatter($formatter);

        self::assertSame($formatter, $output->getFormatter());
        self::assertSame($formatter, $errorOutput->getFormatter());
    }

    public function testSetFormatterOfAnOutputWithoutErrorOutputAppliesToItself(): void
    {
        $output = new ClosureOutput(static function (): void {
        });
        $formatter = new OutputFormatter();

        $output->setFormatter($formatter);

        self::assertSame($formatter, $output->getFormatter());
    }

    public function testSectionIsRefused(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Output sections need a terminal stream; write to the console output instead.');

        (new ClosureOutput(static function (): void {
        }))->section();
    }
}
