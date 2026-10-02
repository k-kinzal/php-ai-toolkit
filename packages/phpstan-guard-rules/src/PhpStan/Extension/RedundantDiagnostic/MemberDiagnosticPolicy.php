<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Extension\RedundantDiagnostic;

use function in_array;

/**
 * Identifies unused-member diagnostics dominated by stricter declaration rules.
 */
final class MemberDiagnosticPolicy
{
    /**
     * PHPStan never emits these for constructors, which the toolkit method rules
     * leave alone, so every remaining hit is a private method the rules forbid.
     *
     * @var list<string>
     */
    private const PRIVATE_METHOD_IDENTIFIERS = [
        'method.finalPrivate',
        'method.unused',
    ];

    /** @var list<string> */
    private const PRIVATE_PROPERTY_IDENTIFIERS = [
        'property.neverRead',
        'property.neverWritten',
        'property.onlyRead',
        'property.onlyWritten',
        'property.unused',
    ];

    /**
     * Reports whether an identifier checks a declaration forbidden by an enabled toolkit rule.
     */
    public function isRedundant(
        ?string $identifier,
        bool $restrictedTestClass,
        bool $noNonPublicMethod,
        bool $noPrivateMethodInTestClass,
        bool $noPropertyInTestClass,
        bool $noClassConstantInTestClass,
    ): bool {
        if ($identifier === null) {
            return false;
        }

        if (in_array($identifier, self::PRIVATE_METHOD_IDENTIFIERS, true)) {
            return $noNonPublicMethod || ($restrictedTestClass && $noPrivateMethodInTestClass);
        }

        if (!$restrictedTestClass) {
            return false;
        }

        if ($noPropertyInTestClass && in_array($identifier, self::PRIVATE_PROPERTY_IDENTIFIERS, true)) {
            return true;
        }

        return $noClassConstantInTestClass && $identifier === 'classConstant.unused';
    }
}
