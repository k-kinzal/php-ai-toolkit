<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Cli;

use function getenv;

use Override;

use function putenv;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The Symfony Console application whose only command is docgen.
 *
 * Help, version, verbosity and colour options behave as in every other
 * Symfony Console tool.
 */
final class DocGenConsole extends ConsoleApplication
{
    /**
     * The version the CLI reports.
     */
    public const VERSION = '1.0.0';

    /**
     * Creates the console for a project working directory.
     */
    public function __construct(string $workingDirectory)
    {
        parent::__construct(DocGenCommand::NAME, self::VERSION);
        $this->setAutoExit(false);
        $this->setCommandLoader(new FactoryCommandLoader([
            DocGenCommand::NAME => static fn (): DocGenCommand => new DocGenCommand($workingDirectory),
        ]));
        $this->setDefaultCommand(DocGenCommand::NAME, true);
    }

    /**
     * Runs the docgen command.
     *
     * The input and output are configured here as well as in run(), so a
     * caller that runs the console in-process still gets --quiet, --verbose
     * and --no-ansi honoured. The verbosity Symfony Console leaves in the
     * environment for child processes is put back afterwards, so one
     * in-process run does not silence the next.
     */
    #[Override]
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $shellVerbosity = [getenv('SHELL_VERBOSITY'), $_ENV['SHELL_VERBOSITY'] ?? null, $_SERVER['SHELL_VERBOSITY'] ?? null];
        $this->configureIO($input, $output);
        try {
            return parent::doRun($input, $output);
        } finally {
            $this->restoreShellVerbosity(...$shellVerbosity);
        }
    }

    /**
     * Puts the shell verbosity of the environment back to what it was.
     *
     * @param false|string $process the process environment value, or false when unset
     * @param mixed $env the $_ENV value, or null when unset
     * @param mixed $server the $_SERVER value, or null when unset
     */
    public function restoreShellVerbosity($process, $env, $server): void
    {
        putenv($process === false ? 'SHELL_VERBOSITY' : 'SHELL_VERBOSITY=' . $process);
        unset($_ENV['SHELL_VERBOSITY'], $_SERVER['SHELL_VERBOSITY']);
        if ($env !== null) {
            $_ENV['SHELL_VERBOSITY'] = $env;
        }

        if ($server !== null) {
            $_SERVER['SHELL_VERBOSITY'] = $server;
        }
    }
}
