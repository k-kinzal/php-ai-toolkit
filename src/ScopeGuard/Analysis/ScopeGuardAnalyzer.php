<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Analysis;

use function count;

use PhpAiToolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces;
use PhpAiToolkit\ScopeGuard\Config\ScopeGuardConfig;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;

/**
 * Runs a full ScopeGuard analysis of a configured project.
 */
final class ScopeGuardAnalyzer
{
    /** @readonly */
    private ProjectScanner $scanner;

    /** @readonly */
    private ScopeChecker $checker;

    /**
     * Creates the analyzer from source scanning and scope checking.
     */
    public function __construct(?ProjectScanner $scanner = null, ?ScopeChecker $checker = null)
    {
        $this->scanner = $scanner ?? new ProjectScanner();
        $this->checker = $checker ?? new ScopeChecker();
    }

    /**
     * Analyzes the configured sources and returns their visibility violations.
     *
     * @throws ScopeGuardException when a configured path is missing or a source file cannot be parsed
     */
    public function analyze(ScopeGuardConfig $config): AnalysisResult
    {
        $scan = $this->scanner->scan($config);
        $violations = $this->checker->violations($scan, new ExemptNamespaces($config->exemptNamespaces));

        return new AnalysisResult(
            $scan->fileCount,
            $this->scopedDeclarationCount($scan),
            count($scan->references),
            $violations,
        );
    }

    /**
     * Returns how many declarations carry an @visibility tag.
     */
    public function scopedDeclarationCount(ProjectScan $scan): int
    {
        $scoped = 0;
        foreach ($scan->index->declarations() as $declaration) {
            if ($declaration->scope->declaredValues !== []) {
                $scoped++;
            }
        }

        return $scoped;
    }
}
