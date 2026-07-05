<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\ErrorFormatter;

use function array_key_exists;

/**
 * Groups PHPStan errors for renderer-specific layouts.
 */
final class ErrorGrouping
{
    /**
     * Groups file-specific errors by file path.
     *
     * @param list<\PHPStan\Analyser\Error> $errors file-specific errors
     * @return array<string, list<\PHPStan\Analyser\Error>>
     */
    public function byFile(array $errors): array
    {
        $grouped = [];
        foreach ($errors as $error) {
            if (!array_key_exists($error->getFile(), $grouped)) {
                $grouped[$error->getFile()] = [];
            }
            $grouped[$error->getFile()][] = $error;
        }

        return $grouped;
    }

    /**
     * Groups file-specific errors by identifier.
     *
     * @param list<\PHPStan\Analyser\Error> $errors file-specific errors
     * @return array<string, list<\PHPStan\Analyser\Error>>
     */
    public function byIdentifier(array $errors): array
    {
        $grouped = [];
        foreach ($errors as $error) {
            $key = $error->getIdentifier() ?? '__none__';
            if (!array_key_exists($key, $grouped)) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $error;
        }

        return $grouped;
    }

    /**
     * Checks whether any identifier count reaches the deduplication threshold.
     *
     * @param list<\PHPStan\Analyser\Error> $errors file-specific errors
     */
    public function shouldDeduplicate(array $errors, int $threshold): bool
    {
        foreach ($this->byIdentifier($errors) as $groupErrors) {
            if (count($groupErrors) >= $threshold) {
                return true;
            }
        }

        return false;
    }
}
