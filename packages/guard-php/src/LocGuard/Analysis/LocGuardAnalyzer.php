<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Analysis;

use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Filesystem\PhpFileFinder;

/**
 * Runs LocGuard analysis across all configured files.
 */
final class LocGuardAnalyzer
{
    /** @readonly */
    private PhpFileFinder $fileFinder;

    /** @readonly */
    private PhpFileAnalyzer $fileAnalyzer;

    /** @readonly */
    private FilePolicyAssigner $policyAssigner;

    /**
     * Creates an analyzer with injectable file discovery and per-file analysis.
     */
    public function __construct(
        ?PhpFileFinder $fileFinder = null,
        ?PhpFileAnalyzer $fileAnalyzer = null,
        ?FilePolicyAssigner $policyAssigner = null,
    ) {
        $this->fileFinder = $fileFinder ?? new PhpFileFinder();
        $this->fileAnalyzer = $fileAnalyzer ?? new PhpFileAnalyzer();
        $this->policyAssigner = $policyAssigner ?? new FilePolicyAssigner();
    }

    /**
     * Analyzes all configured files and returns aggregate metrics and violations.
     */
    public function analyze(LocGuardConfig $config): AnalysisResult
    {
        $files = [];
        $violations = [];

        $assignments = $this->policyAssigner->assign($config, $this->fileFinder->find($config));
        foreach ($assignments as $assignment) {
            $analysis = $this->fileAnalyzer->analyze(
                $assignment->path,
                $assignment->relativePath,
                $assignment->policy->limits,
                $assignment->policy->name,
            );
            $files[] = $analysis->file;
            $violations = array_merge($violations, $analysis->violations);
        }

        return new AnalysisResult($files, $violations);
    }
}
