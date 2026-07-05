<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\ErrorFormatter;

use function count;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;

use function sprintf;
use function trim;

/**
 * Renders PHPStan errors as compact plain text for AI agents.
 */
final class AiRulesAiErrorFormatter
{
    private const DEDUP_THRESHOLD = 3;

    /**
     * Creates the AI error renderer from path and formatting collaborators.
     */
    public function __construct(
        private readonly RelativePathHelper $relativePathHelper,
        private readonly ErrorSourceReader $sourceReader,
        private readonly ErrorGrouping $grouping,
        private readonly ErrorCollectionSummary $summary,
    ) {
    }

    /**
     * Writes AI-readable PHPStan output and returns PHPStan's exit code.
     */
    public function format(AnalysisResult $analysisResult, Output $output): int
    {
        if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
            $output->writeRaw("No errors\n");

            return 0;
        }

        $fileErrors = $analysisResult->getFileSpecificErrors();
        $totalErrors = $analysisResult->getTotalErrorsCount();
        $fileCount = $this->summary->uniqueFileCount($fileErrors);
        $output->writeRaw(sprintf("--- %d %s in %d %s ---\n", $totalErrors, $totalErrors === 1 ? 'error' : 'errors', $fileCount, $fileCount === 1 ? 'file' : 'files'));

        if ($this->grouping->shouldDeduplicate($fileErrors, self::DEDUP_THRESHOLD)) {
            $this->deduplicated($output, $fileErrors);
        } else {
            $this->flat($output, $fileErrors);
        }

        foreach ($analysisResult->getNotFileSpecificErrors() as $error) {
            $output->writeRaw(sprintf("\n[general]\n  %s\n", $error));
        }

        foreach ($analysisResult->getWarnings() as $warning) {
            $output->writeRaw(sprintf("\n[warning]\n  %s\n", $warning));
        }

        return $totalErrors > 0 ? 1 : 0;
    }

    /**
     * Writes one block per file-specific error.
     *
     * @param list<Error> $errors file-specific errors
     */
    public function flat(Output $output, array $errors): void
    {
        foreach ($errors as $error) {
            $identifier = $error->getIdentifier();
            $line = $error->getLine() ?? 0;
            $output->writeRaw(sprintf("\n%s:%d%s\n", $this->relativePathHelper->getRelativePath($error->getFile()), $line, $identifier !== null ? sprintf(' [%s]', $identifier) : ''));
            $output->writeRaw(sprintf("  %s\n", $error->getMessage()));

            $codeLine = $error->getLine() !== null ? $this->sourceReader->read($error->getTraitFilePath() ?? $error->getFile(), $error->getLine()) : null;
            if ($codeLine !== null) {
                $output->writeRaw(sprintf("  > %s\n", trim($codeLine)));
            }

            if ($error->getTip() !== null) {
                $output->writeRaw(sprintf("  Tip: %s\n", $error->getTip()));
            }
        }
    }

    /**
     * Writes errors grouped by identifier.
     *
     * @param list<Error> $errors file-specific errors
     */
    public function deduplicated(Output $output, array $errors): void
    {
        foreach ($this->grouping->byIdentifier($errors) as $identifier => $groupErrors) {
            $count = count($groupErrors);
            $output->writeRaw(sprintf("\n[%s] %d %s:\n", $identifier !== '__none__' ? $identifier : 'unknown', $count, $count === 1 ? 'occurrence' : 'occurrences'));

            foreach ($groupErrors as $error) {
                $line = $error->getLine() ?? 0;
                $codeLine = $error->getLine() !== null ? $this->sourceReader->read($error->getTraitFilePath() ?? $error->getFile(), $error->getLine()) : null;
                $output->writeRaw(sprintf("  %s:%d%s\n", $this->relativePathHelper->getRelativePath($error->getFile()), $line, $codeLine !== null ? ' -- ' . trim($codeLine) : ''));
            }

            $output->writeRaw(sprintf("  Message: %s\n", $groupErrors[0]->getMessage()));
            if ($groupErrors[0]->getTip() !== null) {
                $output->writeRaw(sprintf("  Tip: %s\n", $groupErrors[0]->getTip()));
            }
        }
    }
}
