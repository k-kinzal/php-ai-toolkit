<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Builds errors for missing source and unit test file pairs.
 */
final class SrcUnitTestPairErrorBuilder
{
    /**
     * Builds an error for a source file without its unit test.
     */
    public function missingUnitTest(
        string $srcMarker,
        string $unitTestMarker,
        string $srcRelativePath,
        string $expectedTestRelativePath,
    ): IdentifierRuleError {
        return RuleErrorBuilder::message(sprintf(
            'Source file "%s%s" requires a matching unit test file "%s%s" to keep behavior verifiable.',
            trim($srcMarker, '/'),
            '/' . $srcRelativePath,
            trim($unitTestMarker, '/'),
            '/' . $expectedTestRelativePath
        ))
            ->identifier('customRules.srcWithoutUnitTest')
            ->line(1)
            ->build();
    }

    /**
     * Builds an error for a unit test file without its source file.
     */
    public function missingSource(
        string $srcMarker,
        string $unitTestMarker,
        string $testRelativePath,
        string $expectedSourceRelativePath,
    ): IdentifierRuleError {
        return RuleErrorBuilder::message(sprintf(
            'Unit test file "%s%s" requires a matching source file "%s%s" to avoid stale or orphaned tests.',
            trim($unitTestMarker, '/'),
            '/' . $testRelativePath,
            trim($srcMarker, '/'),
            '/' . $expectedSourceRelativePath
        ))
            ->identifier('customRules.unitTestWithoutSource')
            ->line(1)
            ->build();
    }
}
