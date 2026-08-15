<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Coverage;

use function count;

use DOMDocument;
use DOMElement;

use function is_dir;
use function is_file;
use function ltrim;

use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\DocGenPathResolver;

use function preg_replace;
use function scandir;
use function sprintf;
use function str_ends_with;
use function trim;

/**
 * Reads a PHPUnit XML coverage report directory.
 *
 * The report produced by the PHPUnit option "--coverage-xml" attributes
 * every covered line to the tests that executed it, which is what links
 * methods to their covering test cases.
 */
final class CoverageReader
{
    /** @readonly */
    private DocGenPathResolver $pathResolver;

    /**
     * Creates a coverage reader with path resolution support.
     */
    public function __construct(?DocGenPathResolver $pathResolver = null)
    {
        $this->pathResolver = $pathResolver ?? new DocGenPathResolver();
    }

    /**
     * Reads all per-file coverage reports below a directory.
     *
     * File paths in the report are relative to the report's own source
     * directory, so they are rebased onto the project root using the
     * source attribute of index.xml.
     *
     * @throws DocGenException when the directory does not exist
     */
    public function read(string $directory, string $projectRoot): CoverageIndex
    {
        if (!is_dir($directory)) {
            throw new DocGenException(sprintf('Coverage report directory not found: %s', $directory));
        }

        $sourcePrefix = $this->sourcePrefix($directory, $projectRoot);
        $index = new CoverageIndex();
        $queue = [$directory];
        for ($position = 0; $position < count($queue); $position++) {
            $entries = scandir($queue[$position]);
            foreach ($entries === false ? [] : $entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $path = $queue[$position] . '/' . $entry;
                if (is_dir($path)) {
                    $queue[] = $path;
                } elseif (str_ends_with($entry, '.xml') && $entry !== 'index.xml') {
                    $this->readReportFile($path, $sourcePrefix, $index);
                }
            }
        }

        return $index;
    }

    /**
     * Determines the project-relative prefix of the report's source root.
     */
    public function sourcePrefix(string $directory, string $projectRoot): string
    {
        $indexPath = $directory . '/index.xml';
        if (!is_file($indexPath)) {
            return '';
        }

        $document = new DOMDocument();
        if (@$document->load($indexPath) === false) {
            return '';
        }

        foreach ($document->getElementsByTagName('project') as $project) {
            $source = $project->getAttribute('source');
            if ($source !== '') {
                $relative = $this->pathResolver->relative($projectRoot, $source);

                return $relative === $source ? '' : trim($relative, '/');
            }
        }

        return '';
    }

    /**
     * Reads one per-file coverage report into the index.
     *
     * Newer report versions split the file location into a directory path
     * attribute and a base name; older versions carry one full name.
     */
    public function readReportFile(string $path, string $sourcePrefix, CoverageIndex $index): void
    {
        $document = new DOMDocument();
        if (@$document->load($path) === false) {
            return;
        }

        foreach ($document->getElementsByTagName('file') as $file) {
            if ($file->getAttribute('name') === '') {
                continue;
            }

            $partial = ltrim($file->getAttribute('name'), '/');
            if ($file->getAttribute('path') !== '') {
                $partial = trim($file->getAttribute('path'), '/') . '/' . $partial;
            }

            $relativePath = $sourcePrefix !== '' ? $sourcePrefix . '/' . $partial : $partial;
            $this->readLines($file, $relativePath, $index);
            $this->readMethods($file, $relativePath, $index);
        }
    }

    /**
     * Reads the per-line test attribution of one file element.
     */
    public function readLines(DOMElement $file, string $relativePath, CoverageIndex $index): void
    {
        foreach ($file->getElementsByTagName('line') as $line) {
            $number = (int) $line->getAttribute('nr');
            $tests = [];
            foreach ($line->getElementsByTagName('covered') as $covered) {
                if ($covered->getAttribute('by') !== '') {
                    $tests[] = $this->normalizeTestId($covered->getAttribute('by'));
                }
            }

            if ($number > 0 && $tests !== []) {
                $index->addLine($relativePath, $number, $tests);
            }
        }
    }

    /**
     * Reads the per-method coverage figures of one file element.
     */
    public function readMethods(DOMElement $file, string $relativePath, CoverageIndex $index): void
    {
        foreach ($file->getElementsByTagName('method') as $method) {
            $start = (int) $method->getAttribute('start');
            if ($start > 0) {
                $index->addMethod($relativePath, $start, new MethodCoverage(
                    (int) $method->getAttribute('executable'),
                    (int) $method->getAttribute('executed'),
                    (float) $method->getAttribute('coverage'),
                ));
            }
        }
    }

    /**
     * Strips PHPUnit data set suffixes from a covering test identifier.
     */
    public function normalizeTestId(string $testId): string
    {
        return preg_replace('/\s+with\s+data\s+set\s+.*$/', '', $testId) ?? $testId;
    }
}
