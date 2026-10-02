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
     * @return array{command: string, config: string, format: string, dryRun: bool}
     * @throws PolicyException when arguments are unsupported
     */
    public function parse(array $arguments): array
    {
        $command = array_shift($arguments) ?? 'check';
        if (!in_array($command, ['check', 'apply', 'init', '--help', '-h'], true)) {
            throw new PolicyException('Unknown command "' . $command . '". Use guard check, guard apply or guard init.');
        }
        $config = 'guard.yaml';
        $format = 'text';
        $dryRun = false;
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
            } else {
                throw new PolicyException('Unknown option "' . $argument . '". Use --config=FILE, --format=json or apply --dry-run.');
            }
        }
        if ($config === '' || !in_array($format, ['text', 'json'], true)) {
            throw new PolicyException('Provide a non-empty --config path and --format=text or --format=json.');
        }
        return ['command' => $command, 'config' => $config, 'format' => $format, 'dryRun' => $dryRun];
    }
}
