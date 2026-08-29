<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Cli;

use function sprintf;

use Toolkit\LocGuard\LocGuardException;

/**
 * Parses LocGuard command-line arguments.
 */
final class LocGuardCliArgumentParser
{
    /** @readonly */
    private LocGuardCliValueOptionParser $valueOptionParser;

    /**
     * Creates an argument parser from value-option parsing behavior.
     */
    public function __construct(?LocGuardCliValueOptionParser $valueOptionParser = null)
    {
        $this->valueOptionParser = $valueOptionParser ?? new LocGuardCliValueOptionParser();
    }

    /**
     * Parses LocGuard flags and options.
     *
     * @param list<string> $argv
     * @return array{config: string, explain: ?string, help: bool, reporter: ?string, version: bool}
     *
     * @throws LocGuardException when an option is unknown or missing its value
     */
    public function parse(array $argv): array
    {
        $arguments = ['config' => 'loc.yaml', 'explain' => null, 'help' => false, 'reporter' => null, 'version' => false];
        $skipNext = false;

        foreach ($argv as $index => $arg) {
            if ($skipNext) {
                $skipNext = false;
                continue;
            }

            $valueOption = $this->valueOptionParser->parse($argv, $index);
            if ($valueOption !== null) {
                if ($valueOption->key === 'config') {
                    $arguments['config'] = $valueOption->value;
                } elseif ($valueOption->key === 'explain') {
                    $arguments['explain'] = $valueOption->value;
                } else {
                    $arguments['reporter'] = $valueOption->value;
                }
                $skipNext = $valueOption->consumesNext;
            } elseif ($arg === '--help' || $arg === '-h') {
                $arguments['help'] = true;
            } elseif ($arg === '--version' || $arg === '-V') {
                $arguments['version'] = true;
            } else {
                throw new LocGuardException(sprintf('Unknown option: %s', $arg));
            }
        }

        return $arguments;
    }
}
