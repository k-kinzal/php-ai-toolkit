<?php

declare(strict_types=1);

namespace PhpStanAiRules\ErrorFormatter;

use function array_key_exists;
use function count;
use function file;
use function is_array;
use function is_file;
use function ltrim;
use function max;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use PhpStanAiRules\Support\AgentDetector;
use PhpStanAiRules\Support\FormatMode;

use function rtrim;
use function sprintf;
use function str_pad;
use function str_repeat;
use function strlen;
use function trim;

/**
 * Dual-mode error formatter that switches between human-readable and
 * AI-readable output based on environment detection.
 */
final class AiRulesErrorFormatter implements ErrorFormatter
{
    private const DEDUP_THRESHOLD = 3;

    /**
     * @param RelativePathHelper $relativePathHelper converts absolute paths to relative
     * @param AgentDetector $agentDetector resolves the current output format mode
     */
    public function __construct(
        private readonly RelativePathHelper $relativePathHelper,
        private readonly AgentDetector $agentDetector,
    ) {
    }

    /**
     * Formats analysis errors for either human or AI consumption.
     *
     * @return int 0 when no errors, 1 when errors exist
     */
    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $mode = $this->agentDetector->resolveMode();

        if ($mode === FormatMode::AI) {
            return $this->formatForAi($analysisResult, $output);
        }

        return $this->formatForHuman($analysisResult, $output);
    }

    private function formatForHuman(AnalysisResult $analysisResult, Output $output): int
    {
        if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
            $output->getStyle()->success('No errors');

            return 0;
        }

        $style = $output->getStyle();

        /** @var array<string, list<Error>> $fileErrors */
        $fileErrors = [];
        foreach ($analysisResult->getFileSpecificErrors() as $error) {
            $file = $error->getFile();
            if (!array_key_exists($file, $fileErrors)) {
                $fileErrors[$file] = [];
            }
            $fileErrors[$file][] = $error;
        }

        $fileCount = count($fileErrors);

        foreach ($fileErrors as $file => $errors) {
            $relativePath = $this->relativePathHelper->getRelativePath($file);
            $output->writeLineFormatted('');
            $output->writeLineFormatted(sprintf(' <fg=cyan>%s</>', $relativePath));

            $gutterWidth = $this->calculateGutterWidth($errors);

            foreach ($errors as $error) {
                $output->writeLineFormatted('');
                $this->writeHumanError($output, $error, $file, $gutterWidth);
            }
        }

        foreach ($analysisResult->getNotFileSpecificErrors() as $error) {
            $output->writeLineFormatted('');
            $output->writeLineFormatted(sprintf('  <fg=red>Error:</> %s', $error));
        }

        foreach ($analysisResult->getWarnings() as $warning) {
            $output->writeLineFormatted('');
            $output->writeLineFormatted(sprintf('  <fg=yellow>Warning:</> %s', $warning));
        }

        $output->writeLineFormatted('');

        $totalErrors = $analysisResult->getTotalErrorsCount();
        $warningCount = count($analysisResult->getWarnings());
        $message = sprintf(
            'Found %d %s%s',
            $totalErrors,
            $totalErrors === 1 ? 'error' : 'errors',
            $fileCount > 0 ? sprintf(' in %d %s', $fileCount, $fileCount === 1 ? 'file' : 'files') : '',
        );
        if ($warningCount > 0) {
            $message .= sprintf(' and %d %s', $warningCount, $warningCount === 1 ? 'warning' : 'warnings');
        }

        if ($totalErrors > 0) {
            $style->error($message);
        } elseif ($warningCount > 0) {
            $style->warning($message);
        }

        return $totalErrors > 0 ? 1 : 0;
    }

    private function writeHumanError(Output $output, Error $error, string $file, int $gutterWidth): void
    {
        $line = $error->getLine();
        $lineStr = $line !== null ? (string) $line : '?';
        $gutter = str_pad($lineStr, $gutterWidth, ' ', STR_PAD_LEFT);
        $emptyGutter = str_repeat(' ', $gutterWidth);

        $codeLine = $line !== null ? $this->readSourceLine($error->getTraitFilePath() ?? $file, $line) : null;

        if ($codeLine !== null) {
            $output->writeLineFormatted(sprintf('  <fg=blue>%s</> | %s', $gutter, $codeLine));

            $trimmedCode = ltrim($codeLine);
            $leadingSpaces = strlen($codeLine) - strlen($trimmedCode);
            $caretLength = max(1, strlen(rtrim($trimmedCode)));
            $carets = str_repeat(' ', $leadingSpaces) . str_repeat('^', $caretLength);

            $output->writeLineFormatted(sprintf('  %s | <fg=red>%s</>', $emptyGutter, $carets));
        }

        $identifier = $error->getIdentifier();
        $identifierStr = $identifier !== null ? sprintf('<fg=magenta>%s</>: ', $identifier) : '';
        $output->writeLineFormatted(sprintf('  %s%s', $identifierStr, $error->getMessage()));

        $tip = $error->getTip();
        if ($tip !== null) {
            $output->writeLineFormatted(sprintf('  <fg=yellow>Tip:</> %s', $tip));
        }
    }

    /**
     * @param list<Error> $errors
     */
    private function calculateGutterWidth(array $errors): int
    {
        $maxLine = 1;
        foreach ($errors as $error) {
            $line = $error->getLine();
            if ($line !== null && $line > $maxLine) {
                $maxLine = $line;
            }
        }

        return max(3, strlen((string) $maxLine));
    }

    private function formatForAi(AnalysisResult $analysisResult, Output $output): int
    {
        if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
            $output->writeRaw("No errors\n");

            return 0;
        }

        $fileErrors = $analysisResult->getFileSpecificErrors();
        $notFileErrors = $analysisResult->getNotFileSpecificErrors();
        $warnings = $analysisResult->getWarnings();
        $totalErrors = $analysisResult->getTotalErrorsCount();
        $fileCount = $this->countUniqueFiles($fileErrors);

        $output->writeRaw(sprintf(
            "--- %d %s in %d %s ---\n",
            $totalErrors,
            $totalErrors === 1 ? 'error' : 'errors',
            $fileCount,
            $fileCount === 1 ? 'file' : 'files',
        ));

        if ($this->shouldUseDedupFormat($fileErrors)) {
            $this->writeAiDeduplicated($output, $fileErrors);
        } else {
            $this->writeAiFlat($output, $fileErrors);
        }

        foreach ($notFileErrors as $error) {
            $output->writeRaw(sprintf("\n[general]\n  %s\n", $error));
        }

        foreach ($warnings as $warning) {
            $output->writeRaw(sprintf("\n[warning]\n  %s\n", $warning));
        }

        return $totalErrors > 0 ? 1 : 0;
    }

    /**
     * @param list<Error> $errors
     */
    private function writeAiFlat(Output $output, array $errors): void
    {
        foreach ($errors as $error) {
            $relativePath = $this->relativePathHelper->getRelativePath($error->getFile());
            $line = $error->getLine() ?? 0;
            $identifier = $error->getIdentifier();

            $output->writeRaw(sprintf(
                "\n%s:%d%s\n",
                $relativePath,
                $line,
                $identifier !== null ? sprintf(' [%s]', $identifier) : '',
            ));
            $output->writeRaw(sprintf("  %s\n", $error->getMessage()));

            $codeLine = $error->getLine() !== null
                ? $this->readSourceLine($error->getTraitFilePath() ?? $error->getFile(), $error->getLine())
                : null;
            if ($codeLine !== null) {
                $output->writeRaw(sprintf("  > %s\n", trim($codeLine)));
            }

            $tip = $error->getTip();
            if ($tip !== null) {
                $output->writeRaw(sprintf("  Tip: %s\n", $tip));
            }
        }
    }

    /**
     * @param list<Error> $errors
     */
    private function writeAiDeduplicated(Output $output, array $errors): void
    {
        /** @var array<string, list<Error>> $grouped */
        $grouped = [];
        foreach ($errors as $error) {
            $key = $error->getIdentifier() ?? '__none__';
            if (!array_key_exists($key, $grouped)) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $error;
        }

        foreach ($grouped as $identifier => $groupErrors) {
            $count = count($groupErrors);
            $identifierLabel = $identifier !== '__none__' ? $identifier : 'unknown';

            $output->writeRaw(sprintf(
                "\n[%s] %d %s:\n",
                $identifierLabel,
                $count,
                $count === 1 ? 'occurrence' : 'occurrences',
            ));

            foreach ($groupErrors as $error) {
                $relativePath = $this->relativePathHelper->getRelativePath($error->getFile());
                $line = $error->getLine() ?? 0;

                $codeLine = $error->getLine() !== null
                    ? $this->readSourceLine($error->getTraitFilePath() ?? $error->getFile(), $error->getLine())
                    : null;
                $codeSnippet = $codeLine !== null ? ' -- ' . trim($codeLine) : '';

                $output->writeRaw(sprintf("  %s:%d%s\n", $relativePath, $line, $codeSnippet));
            }

            $firstError = $groupErrors[0];
            $output->writeRaw(sprintf("  Message: %s\n", $firstError->getMessage()));

            $tip = $firstError->getTip();
            if ($tip !== null) {
                $output->writeRaw(sprintf("  Tip: %s\n", $tip));
            }
        }
    }

    /**
     * @param list<Error> $errors
     */
    private function shouldUseDedupFormat(array $errors): bool
    {
        /** @var array<string, int> $counts */
        $counts = [];
        foreach ($errors as $error) {
            $key = $error->getIdentifier() ?? '__none__';
            if (!array_key_exists($key, $counts)) {
                $counts[$key] = 0;
            }
            $counts[$key]++;
        }

        foreach ($counts as $count) {
            if ($count >= self::DEDUP_THRESHOLD) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<Error> $errors
     */
    private function countUniqueFiles(array $errors): int
    {
        $files = [];
        foreach ($errors as $error) {
            $files[$error->getFile()] = true;
        }

        return count($files);
    }

    private function readSourceLine(string $filePath, int $line): ?string
    {
        if (!is_file($filePath)) {
            return null;
        }

        $lines = @file($filePath);
        if (!is_array($lines)) {
            return null;
        }

        $index = $line - 1;
        if (!array_key_exists($index, $lines)) {
            return null;
        }

        return rtrim($lines[$index], "\r\n");
    }
}
