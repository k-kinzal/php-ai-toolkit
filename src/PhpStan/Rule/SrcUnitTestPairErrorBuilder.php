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
            'Create unit test file "%s%s" for source file "%s%s".',
            trim($unitTestMarker, '/'),
            '/' . $expectedTestRelativePath,
            trim($srcMarker, '/'),
            '/' . $srcRelativePath
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
            'Create source file "%s%s" for unit test file "%s%s", or remove the stale test.',
            trim($srcMarker, '/'),
            '/' . $expectedSourceRelativePath,
            trim($unitTestMarker, '/'),
            '/' . $testRelativePath
        ))
            ->identifier('customRules.unitTestWithoutSource')
            ->line(1)
            ->build();
    }
}
