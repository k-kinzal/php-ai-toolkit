<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter\Legacy;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Extracts comparison diffs from PHPUnit 9 assertion failures.
 */
final class LegacyFailureDiffResolver
{
    /**
     * Returns the comparison diff attached to a failure, when available.
     */
    public function resolve(AssertionFailedError $failure): ?string
    {
        if (!$failure instanceof ExpectationFailedException) {
            return null;
        }

        $comparisonFailure = $failure->getComparisonFailure();
        if ($comparisonFailure === null) {
            return null;
        }

        return $comparisonFailure->getDiff();
    }
}
