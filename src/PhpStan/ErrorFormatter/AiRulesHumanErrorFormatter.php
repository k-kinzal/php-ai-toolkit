<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\ErrorFormatter;

use function count;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;

use function sprintf;

/**
 * Renders PHPStan errors for human terminal output.
 */
final class AiRulesHumanErrorFormatter
{
    /** @readonly */
    private HumanFileErrorPrinter $fileErrorPrinter;

    /**
     * Creates the human error renderer from path and formatting collaborators.
     */
    public function __construct(
        RelativePathHelper $relativePathHelper,
        ErrorSourceReader $sourceReader,
        ErrorGutter $gutter,
        /** @readonly */
        private ErrorGrouping $grouping,
        /** @readonly */
        private ErrorCollectionSummary $summary,
        ?HumanFileErrorPrinter $fileErrorPrinter = null,
    ) {
        $this->fileErrorPrinter = $fileErrorPrinter ?? new HumanFileErrorPrinter($relativePathHelper, $gutter, new HumanErrorPrinter($sourceReader, $gutter));
    }

    /**
     * Writes human-readable PHPStan output and returns PHPStan's exit code.
     */
    public function format(AnalysisResult $analysisResult, Output $output): int
    {
        if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
            $output->getStyle()->success('No errors');

            return 0;
        }

        $fileErrors = $this->grouping->byFile($analysisResult->getFileSpecificErrors());
        $this->fileErrorPrinter->write($fileErrors, $output);

        foreach ($analysisResult->getNotFileSpecificErrors() as $error) {
            $output->writeLineFormatted('');
            $output->writeLineFormatted(sprintf('  <fg=red>Error:</> %s', $error));
        }

        foreach ($analysisResult->getWarnings() as $warning) {
            $output->writeLineFormatted('');
            $output->writeLineFormatted(sprintf('  <fg=yellow>Warning:</> %s', $warning));
        }

        $output->writeLineFormatted('');
        $message = $this->summary->humanMessage($analysisResult->getTotalErrorsCount(), count($analysisResult->getWarnings()), count($fileErrors));
        if ($analysisResult->getTotalErrorsCount() > 0) {
            $output->getStyle()->error($message);
        } elseif (count($analysisResult->getWarnings()) > 0) {
            $output->getStyle()->warning($message);
        }

        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }
}
