<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Extension\RedundantDiagnostic;

use function array_map;
use function rtrim;
use function str_starts_with;

/**
 * Identifies class names covered by the toolkit's restricted test policy.
 */
final class RestrictedTestClassPolicy
{
    /** @var list<string> */
    private array $testNamespacePrefixes;

    /** @var list<string> */
    private array $restrictedTestNamespacePrefixes;

    /**
     * Creates the policy from test and restricted namespace prefixes.
     *
     * @param list<string> $testNamespacePrefixes
     * @param list<string> $restrictedTestNamespacePrefixes
     */
    public function __construct(
        array $testNamespacePrefixes = ['Tests'],
        array $restrictedTestNamespacePrefixes = ['Tests\\Unit', 'Tests\\Integration'],
    ) {
        $normalize = static fn (string $prefix): string => rtrim($prefix, '\\') . '\\';
        $this->testNamespacePrefixes = array_map($normalize, $testNamespacePrefixes);
        $this->restrictedTestNamespacePrefixes = array_map($normalize, $restrictedTestNamespacePrefixes);
    }

    /**
     * Reports whether a class name belongs to both configured namespace sets.
     */
    public function isRestricted(?string $className): bool
    {
        if ($className === null) {
            return false;
        }

        $className = ltrim($className, '\\');
        $isTestClass = false;
        foreach ($this->testNamespacePrefixes as $prefix) {
            if (str_starts_with($className, $prefix)) {
                $isTestClass = true;
                break;
            }
        }

        if (!$isTestClass) {
            return false;
        }

        foreach ($this->restrictedTestNamespacePrefixes as $prefix) {
            if (str_starts_with($className, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
