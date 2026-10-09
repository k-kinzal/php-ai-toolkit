<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Coverage;

use function array_keys;
use function ksort;
use function sort;

/**
 * Query index over per-line test attribution and per-method coverage.
 */
final class CoverageIndex
{
    /** @var array<string, array<int, array<string, bool>>> */
    private array $lineTests = [];

    /** @var array<string, array<int, MethodCoverage>> */
    private array $methodsByLine = [];

    /**
     * Records the tests covering one source line.
     *
     * @param list<string> $tests
     */
    public function addLine(string $file, int $line, array $tests): void
    {
        foreach ($tests as $test) {
            $this->lineTests[$file][$line][$test] = true;
        }
    }

    /**
     * Records the coverage figures of one method by its start line.
     */
    public function addMethod(string $file, int $startLine, MethodCoverage $coverage): void
    {
        $this->methodsByLine[$file][$startLine] = $coverage;
    }

    /**
     * Returns the sorted test identifiers covering a line range.
     *
     * @return list<string>
     */
    public function testsForRange(string $file, int $startLine, int $endLine): array
    {
        $tests = [];
        foreach ($this->lineTests[$file] ?? [] as $line => $lineTests) {
            if ($line >= $startLine && $line <= $endLine) {
                foreach (array_keys($lineTests) as $test) {
                    $tests[$test] = true;
                }
            }
        }

        $names = array_keys($tests);
        sort($names);

        return $names;
    }

    /**
     * Returns the coverage of the method starting near the given line.
     *
     * The XML report anchors methods at their signature line while the
     * parser may anchor at a preceding attribute, so a small range of
     * candidate start lines is probed.
     */
    public function methodAt(string $file, int $startLine, int $endLine): ?MethodCoverage
    {
        $methods = $this->methodsByLine[$file] ?? [];
        ksort($methods);
        foreach ($methods as $line => $coverage) {
            if ($line >= $startLine && $line <= $endLine) {
                return $coverage;
            }
        }

        return null;
    }

    /**
     * Reports whether any coverage data was loaded.
     */
    public function isEmpty(): bool
    {
        return $this->lineTests === [] && $this->methodsByLine === [];
    }
}
