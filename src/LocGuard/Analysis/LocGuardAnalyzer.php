<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Config\LocGuardConfig;
use PhpAiToolkit\LocGuard\Filesystem\PhpFileFinder;

/**
 * Runs LocGuard analysis across all configured files.
 */
final class LocGuardAnalyzer
{
    /**
     * Creates an analyzer with injectable file discovery and per-file analysis.
     */
    public function __construct(
        private readonly PhpFileFinder $fileFinder = new PhpFileFinder(),
        private readonly PhpFileAnalyzer $fileAnalyzer = new PhpFileAnalyzer(),
    ) {
    }

    /**
     * Analyzes all configured files and returns aggregate metrics and violations.
     */
    public function analyze(LocGuardConfig $config): AnalysisResult
    {
        $files = [];
        $violations = [];

        foreach ($this->fileFinder->find($config) as $path => $relativePath) {
            $analysis = $this->fileAnalyzer->analyze($path, $relativePath, $config->limits);
            $files[] = $analysis->file;
            $violations = array_merge($violations, $analysis->violations);
        }

        return new AnalysisResult($files, $violations);
    }
}
