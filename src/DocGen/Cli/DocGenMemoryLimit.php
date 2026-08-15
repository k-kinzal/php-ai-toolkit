<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Cli;

use function ini_get;
use function ini_set;
use function preg_match;
use function strtoupper;
use function trim;

/**
 * Applies the memory limit used while generating documentation.
 *
 * Documenting a large dependency tree needs more than the common default
 * of 128M, so the limit is raised to a floor unless the environment
 * already allows more. An explicit limit always wins, including a lower
 * one, so callers stay in control.
 */
final class DocGenMemoryLimit
{
    /**
     * Memory limit applied when the environment allows less.
     */
    public const FLOOR = '512M';

    /**
     * Applies the requested limit, or raises the current one to the floor.
     */
    public function apply(?string $requested): void
    {
        if ($requested !== null) {
            ini_set('memory_limit', $requested);

            return;
        }

        $currentBytes = $this->bytes(ini_get('memory_limit'));
        if ($currentBytes >= 0 && $currentBytes < $this->bytes(self::FLOOR)) {
            ini_set('memory_limit', self::FLOOR);
        }
    }

    /**
     * Converts a memory limit value to bytes, or -1 when it is unlimited.
     */
    public function bytes(string $limit): int
    {
        $value = trim($limit);
        if ($value === '-1') {
            return -1;
        }

        if (preg_match('/^(\d+)([KMG]?)$/i', $value, $match) !== 1) {
            return -1;
        }

        $factors = ['' => 1, 'K' => 1024, 'M' => 1048576, 'G' => 1073741824];

        return (int) $match[1] * $factors[strtoupper($match[2])];
    }
}
