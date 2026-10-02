<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Cli;

use function sprintf;
use function strlen;

use Toolkit\LocGuard\LocGuardException;

/**
 * Parses LocGuard CLI options that carry a string value.
 */
final class LocGuardCliValueOptionParser
{
    /** @var array<string, string> */
    private const OPTIONS = [
        '--config' => 'config',
        '--explain' => 'explain',
        '--reporter' => 'reporter',
        '--format' => 'reporter',
    ];

    /**
     * Parses an exact or equals-form value option when recognized.
     *
     * @param list<string> $argv
     *
     * @throws LocGuardException when a recognized option has no value
     */
    public function parse(array $argv, int $index): ?LocGuardCliValueOption
    {
        $argument = $argv[$index];
        foreach (self::OPTIONS as $option => $key) {
            if ($argument === $option) {
                if (!isset($argv[$index + 1]) || str_starts_with($argv[$index + 1], '-')) {
                    throw new LocGuardException(sprintf('Missing value for %s.', $option));
                }

                return new LocGuardCliValueOption($key, $argv[$index + 1], true);
            }

            if (str_starts_with($argument, $option . '=')) {
                $value = substr($argument, strlen($option) + 1);
                if ($value === '') {
                    throw new LocGuardException(sprintf('Missing value for %s.', $option));
                }

                return new LocGuardCliValueOption($key, $value, false);
            }
        }

        return null;
    }
}
