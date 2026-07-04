<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Support;

use PHPStan\Analyser\Scope;

/**
 * Detects whether the current scope is within a test class namespace.
 */
final class TestClassScope
{
    /** @var list<string> */
    private readonly array $testNamespacePrefixes;

    /** @var list<string> */
    private readonly array $restrictedTestNamespacePrefixes;

    /**
     * @param list<string> $testNamespacePrefixes namespace prefixes that identify test classes
     * @param list<string> $restrictedTestNamespacePrefixes namespace prefixes for stricter test rules
     */
    public function __construct(
        array $testNamespacePrefixes = ['Tests'],
        array $restrictedTestNamespacePrefixes = ['Tests\\Unit', 'Tests\\Integration'],
    ) {
        $this->testNamespacePrefixes = array_map(
            static fn (string $prefix): string => rtrim($prefix, '\\') . '\\',
            $testNamespacePrefixes,
        );
        $this->restrictedTestNamespacePrefixes = array_map(
            static fn (string $prefix): string => rtrim($prefix, '\\') . '\\',
            $restrictedTestNamespacePrefixes,
        );
    }

    /**
     * Determines whether the current scope is inside a test class.
     */
    public function isTestClass(Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return false;
        }

        $className = ltrim($classReflection->getName(), '\\');

        foreach ($this->testNamespacePrefixes as $prefix) {
            if (str_starts_with($className, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether the current scope is inside a restricted test class.
     */
    public function isRestrictedTestClass(Scope $scope): bool
    {
        if (!$this->isTestClass($scope)) {
            return false;
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return false;
        }

        $className = ltrim($classReflection->getName(), '\\');

        foreach ($this->restrictedTestNamespacePrefixes as $prefix) {
            if (str_starts_with($className, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
