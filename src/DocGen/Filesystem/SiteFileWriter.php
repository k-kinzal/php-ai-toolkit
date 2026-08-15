<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Filesystem;

use function dirname;
use function file_put_contents;
use function is_dir;
use function mkdir;

use PhpAiToolkit\DocGen\DocGenException;

use function sprintf;

/**
 * Writes generated files below the site output directory.
 */
final class SiteFileWriter
{
    /**
     * Writes one file, creating parent directories as needed.
     *
     * @throws DocGenException when the directory or file cannot be written
     */
    public function write(string $outputRoot, string $relativePath, string $contents): void
    {
        $path = $outputRoot . '/' . $relativePath;
        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new DocGenException(sprintf('Failed to create output directory: %s', $directory));
        }

        if (@file_put_contents($path, $contents) === false) {
            throw new DocGenException(sprintf('Failed to write output file: %s', $path));
        }
    }
}
