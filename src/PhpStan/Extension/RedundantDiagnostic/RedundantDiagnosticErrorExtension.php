<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Extension\RedundantDiagnostic;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;

/**
 * Suppresses PHPStan diagnostics made redundant by enabled toolkit rules.
 */
final class RedundantDiagnosticErrorExtension implements IgnoreErrorExtension
{
    /** @readonly */
    private MemberDiagnosticPolicy $memberDiagnosticPolicy;

    /** @readonly */
    private TestControlFlowDiagnosticPolicy $testControlFlowDiagnosticPolicy;

    /** @readonly */
    private DirectThrowDiagnosticPolicy $directThrowDiagnosticPolicy;

    /** @readonly */
    private RestrictedTestClassPolicy $restrictedTestClassPolicy;

    /**
     * Creates the extension from toolkit rule switches and dominance policies.
     *
     * @param list<string> $testNamespacePrefixes
     * @param list<string> $restrictedTestNamespacePrefixes
     */
    public function __construct(
        array $testNamespacePrefixes,
        array $restrictedTestNamespacePrefixes,
        /** @readonly */
        private bool $noNonPublicMethod,
        /** @readonly */
        private bool $noPrivateMethodInTestClass,
        /** @readonly */
        private bool $noPropertyInTestClass,
        /** @readonly */
        private bool $noClassConstantInTestClass,
        /** @readonly */
        private bool $noControlFlowInTestMethod,
        /** @readonly */
        private bool $requireThrowsTagOnDirectThrow,
        ?MemberDiagnosticPolicy $memberDiagnosticPolicy = null,
        ?TestControlFlowDiagnosticPolicy $testControlFlowDiagnosticPolicy = null,
        ?DirectThrowDiagnosticPolicy $directThrowDiagnosticPolicy = null,
        ?RestrictedTestClassPolicy $restrictedTestClassPolicy = null,
    ) {
        $this->memberDiagnosticPolicy = $memberDiagnosticPolicy ?? new MemberDiagnosticPolicy();
        $this->testControlFlowDiagnosticPolicy = $testControlFlowDiagnosticPolicy ?? new TestControlFlowDiagnosticPolicy();
        $this->directThrowDiagnosticPolicy = $directThrowDiagnosticPolicy ?? new DirectThrowDiagnosticPolicy();
        $this->restrictedTestClassPolicy = $restrictedTestClassPolicy ?? new RestrictedTestClassPolicy(
            $testNamespacePrefixes,
            $restrictedTestNamespacePrefixes,
        );
    }

    /**
     * Reports whether an enabled toolkit rule provides the sole actionable diagnostic.
     */
    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        $identifier = $error->getIdentifier();
        $classReflection = $scope->getClassReflection();
        $restrictedTestClass = $this->restrictedTestClassPolicy->isRestricted(
            $classReflection === null ? null : $classReflection->getName(),
        );
        if ($this->memberDiagnosticPolicy->isRedundant(
            $identifier,
            $restrictedTestClass,
            $this->noNonPublicMethod,
            $this->noPrivateMethodInTestClass,
            $this->noPropertyInTestClass,
            $this->noClassConstantInTestClass,
        )) {
            return true;
        }

        if ($this->noControlFlowInTestMethod
            && $this->testControlFlowDiagnosticPolicy->isRedundant($node, $scope, $restrictedTestClass)
        ) {
            return true;
        }

        return $this->requireThrowsTagOnDirectThrow
            && $this->directThrowDiagnosticPolicy->isRedundant($identifier, $error->getLine(), $node);
    }
}
