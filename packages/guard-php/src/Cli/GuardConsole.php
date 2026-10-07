<?php

declare(strict_types=1);

namespace Guard\Cli;

use Guard\Cli\Command\ApplyCommand;
use Guard\Cli\Command\CheckCommand;
use Guard\Cli\Command\GuardCommand;
use Guard\Cli\Command\InitCommand;
use Guard\Extension\Registry;
use Override;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The Symfony Console application with the check, apply and init commands.
 *
 * Help, version, verbosity, colour and shell completion behave as in every
 * other Symfony Console tool. guard alone runs check, guard --help lists
 * the commands, and a command name guard does not know exits 2.
 */
final class GuardConsole extends ConsoleApplication
{
    /**
     * The version the CLI reports.
     */
    public const VERSION = '1.0.0';

    /**
     * Creates the console for a project directory.
     */
    public function __construct(string $directory, ?Registry $registry = null)
    {
        parent::__construct('guard', self::VERSION);
        $this->setAutoExit(false);
        $this->setCommandLoader(new FactoryCommandLoader([
            'check' => static fn (): CheckCommand => new CheckCommand($directory, $registry),
            'apply' => static fn (): ApplyCommand => new ApplyCommand($directory, $registry),
            'init' => static fn (): InitCommand => new InitCommand($directory),
        ]));
        $this->setDefaultCommand('check');
    }

    /**
     * Returns the text guard list opens with.
     */
    #[Override]
    public function getHelp(): string
    {
        return $this->getLongVersion() . "\n\nChecks and repairs a project against the policies in its guard.yaml. Running guard without a command runs check.";
    }

    /**
     * Runs the command the input names.
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
            $name = $this->getCommandName($input);
            if ($name === null && $input->hasParameterOption(['--help', '-h'], true)) {
                return parent::doRun(new ArrayInput(['command' => 'list']), $output);
            }
            $problem = $name === null ? null : $this->unknownCommand($name);
            if ($problem !== null) {
                $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
                $errorOutput->writeln(['Guard error: ' . $problem, 'Run "guard list" to see the available commands.'], OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET);

                return GuardCommand::INVALID;
            }

            return parent::doRun($input, $output);
        } finally {
            $this->restoreShellVerbosity(...$shellVerbosity);
        }
    }

    /**
     * Explains why a command name names no single command, or returns null when it does.
     *
     * A name may be cut short as long as only one command starts with it,
     * such as ch for check.
     */
    public function unknownCommand(string $name): ?string
    {
        $names = array_keys($this->all());
        if (in_array($name, $names, true)) {
            return null;
        }
        $matches = array_values(array_filter($names, static fn (string $command): bool => str_starts_with($command, $name) && !str_starts_with($command, '_')));
        if (count($matches) === 1) {
            return null;
        }
        if ($matches !== []) {
            sort($matches);

            return sprintf('Command "%s" is ambiguous. Name one of: %s.', $name, implode(', ', $matches));
        }
        $similar = array_values(array_filter($names, static fn (string $command): bool => !str_starts_with($command, '_') && levenshtein($name, $command) <= max(2, intdiv(strlen($name), 3))));

        return sprintf('Command "%s" is not defined.', $name) . ($similar === [] ? '' : sprintf(' Did you mean %s?', implode(' or ', $similar)));
    }

    /**
     * Returns the global options, with --help described the way guard treats it.
     */
    #[Override]
    protected function getDefaultInputDefinition(): InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();
        $options = $definition->getOptions();
        $options['help'] = new InputOption('help', 'h', InputOption::VALUE_NONE, 'Display help for the given command, or list the commands when none is given');
        $definition->setOptions(array_values($options));

        return $definition;
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
