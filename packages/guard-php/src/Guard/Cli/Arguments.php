<?php

declare(strict_types=1);

namespace Toolkit\Guard\Cli;

use Toolkit\Guard\Policy\PolicyException;

/**
 * Parses guard check, apply and init arguments without ignoring unknown options.
 */
final class Arguments
{
    /**
     * @param list<string> $arguments
     * @return array{command: string, config: string, format: string, dryRun: bool, imports: ?list<string>}
     * @throws PolicyException when arguments are unsupported
     */
    public function parse(array $arguments): array
    {
        $command = array_shift($arguments) ?? 'check';
        if (!in_array($command, ['check', 'apply', 'init', '--help', '-h'], true)) {
            throw new PolicyException('Unknown command "' . $command . '". Use guard check, guard apply or guard init.');
        }

        return $this->options($command, $arguments);
    }

    /**
     * Reads options for one already validated command.
     *
     * @param list<string> $arguments
     * @return array{command: string, config: string, format: string, dryRun: bool, imports: ?list<string>}
     * @throws PolicyException when an option is unsupported or incomplete
     */
    public function options(string $command, array $arguments): array
    {
        $config = 'guard.yaml';
        $format = 'text';
        $dryRun = false;
        $imports = null;
        while ($arguments !== []) {
            $argument = array_shift($arguments);
            if ($argument === '--dry-run' && $command === 'apply') {
                $dryRun = true;
            } elseif ($argument === '--config' || $argument === '-c') {
                $config = array_shift($arguments) ?? '';
            } elseif (str_starts_with($argument, '--config=')) {
                $config = substr($argument, 9);
            } elseif (str_starts_with($argument, '--format=')) {
                $format = substr($argument, 9);
            } elseif ($command === 'init' && ($argument === '--import' || str_starts_with($argument, '--import='))) {
                $value = $argument === '--import' ? (array_shift($arguments) ?? '') : substr($argument, 9);
                $imports = $this->importNames($value, $imports ?? []);
            } else {
                throw new PolicyException('Unknown option "' . $argument . '". Use --config=FILE, --format=json, init --import=NAME or apply --dry-run.');
            }
        }
        if ($config === '' || !in_array($format, ['text', 'json'], true)) {
            throw new PolicyException('Provide a non-empty --config path and --format=text or --format=json.');
        }

        return ['command' => $command, 'config' => $config, 'format' => $format, 'dryRun' => $dryRun, 'imports' => $imports];
    }

    /**
     * Appends comma-separated preset names from one --import option.
     *
     * @param list<string> $imports
     * @return list<string>
     * @throws PolicyException when a name is empty
     */
    public function importNames(string $value, array $imports): array
    {
        if ($value === '') {
            throw new PolicyException('Provide one or more preset names for --import, separated by commas.');
        }
        foreach (explode(',', $value) as $name) {
            if ($name === '') {
                throw new PolicyException('Provide one or more preset names for --import, separated by commas.');
            }
            $imports[] = $name;
        }

        return $imports;
    }
}
