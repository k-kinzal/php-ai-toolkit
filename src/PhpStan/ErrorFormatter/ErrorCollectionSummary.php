<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\ErrorFormatter;

use function count;
use function sprintf;

/**
 * Summarizes PHPStan error collections for renderer headers and footers.
 */
final class ErrorCollectionSummary
{
    /**
     * Builds the final human summary message.
     */
    public function humanMessage(int $totalErrors, int $warningCount, int $fileCount): string
    {
        $message = sprintf(
            'Found %d %s%s',
            $totalErrors,
            $totalErrors === 1 ? 'error' : 'errors',
            $fileCount > 0 ? sprintf(' in %d %s', $fileCount, $fileCount === 1 ? 'file' : 'files') : '',
        );

        if ($warningCount > 0) {
            $message .= sprintf(' and %d %s', $warningCount, $warningCount === 1 ? 'warning' : 'warnings');
        }

        return $message;
    }

    /**
     * Counts unique files in file-specific errors.
     *
     * @param list<\PHPStan\Analyser\Error> $errors file-specific errors
     */
    public function uniqueFileCount(array $errors): int
    {
        $files = [];
        foreach ($errors as $error) {
            $files[$error->getFile()] = true;
        }

        return count($files);
    }
}
