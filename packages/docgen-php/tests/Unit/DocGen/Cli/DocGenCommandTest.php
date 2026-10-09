<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Toolkit\DocGen\Analysis\Config\BaseUrl;
use Toolkit\DocGen\Analysis\Config\RepositoryUrl;
use Toolkit\DocGen\Cli\DocGenCliArgumentParser;
use Toolkit\DocGen\Cli\DocGenCommand;
use Toolkit\DocGen\Cli\DocGenHelpText;
use Toolkit\DocGen\Cli\DocGenOutputWriter;
use Toolkit\DocGen\DocGenException;

/**
 * @covers \Toolkit\DocGen\Cli\DocGenCommand
 * @uses \Toolkit\DocGen\Cli\DocGenCliArgumentParser
 * @uses \Toolkit\DocGen\Cli\DocGenHelpText
 * @uses \Toolkit\DocGen\Cli\DocGenOutputWriter
 * @uses \Toolkit\DocGen\Analysis\Config\BaseUrl
 * @uses \Toolkit\DocGen\Analysis\Config\RepositoryUrl
 * @uses \Toolkit\DocGen\DocGenException
 */
#[CoversClass(DocGenCommand::class)]
#[UsesClass(DocGenCliArgumentParser::class)]
#[UsesClass(DocGenHelpText::class)]
#[UsesClass(DocGenOutputWriter::class)]
#[UsesClass(BaseUrl::class)]
#[UsesClass(RepositoryUrl::class)]
#[UsesClass(DocGenException::class)]
final class DocGenCommandTest extends TestCase
{
    public function testConfigureDeclaresTheNameTheOptionsAndTheHelp(): void
    {
        $command = new DocGenCommand(sys_get_temp_dir());

        self::assertSame('docgen', $command->getName());
        self::assertStringStartsWith('Generate a static HTML documentation site', $command->getDescription());
        self::assertSame((new DocGenHelpText())->text(), $command->getHelp());
        self::assertTrue($command->getDefinition()->hasOption('packages'));
        self::assertTrue($command->getDefinition()->hasShortcut('o'));
    }

    public function testExecuteReportsAMalformedOptionValueOnStandardError(): void
    {
        $tester = new CommandTester(new DocGenCommand(sys_get_temp_dir()));

        self::assertSame(2, $tester->execute(['--memory-limit' => 'plenty'], ['capture_stderr_separately' => true]));
        self::assertSame('', $tester->getDisplay());
        self::assertStringStartsWith('DocGen error: Invalid --memory-limit value: plenty.', $tester->getErrorOutput());
    }

    public function testRunReportsAnUnknownOptionWithAPointerToTheHelp(): void
    {
        $tester = new CommandTester(new DocGenCommand(sys_get_temp_dir()));

        self::assertSame(2, $tester->execute(['--bogus' => true], ['capture_stderr_separately' => true]));
        self::assertSame("DocGen error: The \"--bogus\" option does not exist.\nRun \"docgen --help\" to see every option.\n", $tester->getErrorOutput());
    }
}
