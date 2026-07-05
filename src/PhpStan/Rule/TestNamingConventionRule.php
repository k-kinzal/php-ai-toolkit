<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final class TestNamingConventionRule implements Rule
{
    /** @readonly */
    private RulePathNormalizer $pathNormalizer;

    /** @readonly */
    private SourceFileRuleMatcher $sourceFileMatcher;

    /** @readonly */
    private PublicMethodTestCoverageValidator $publicMethodValidator;

    /** @readonly */
    private TestMethodNameValidator $testMethodNameValidator;

    /** @readonly */
    private ProviderNameValidator $providerNameValidator;

    /**
     * @param TestClassScope $testClassScope test class scope detector
     * @param string $srcMarker path marker identifying source directories
     * @param string $unitTestMarker path marker identifying unit test directories
     */
    public function __construct(
        /** @readonly */
        private TestClassScope $testClassScope,
        /** @readonly */
        private string $srcMarker = '/src/',
        string $unitTestMarker = '/tests/Unit/',
        ?RulePathNormalizer $pathNormalizer = null,
        ?SourceFileRuleMatcher $sourceFileMatcher = null,
        ?PublicMethodTestCoverageValidator $publicMethodValidator = null,
        ?TestMethodNameValidator $testMethodNameValidator = null,
        ?ProviderNameValidator $providerNameValidator = null,
    ) {
        $this->pathNormalizer = $pathNormalizer ?? new RulePathNormalizer();
        $this->sourceFileMatcher = $sourceFileMatcher ?? new SourceFileRuleMatcher();
        $this->publicMethodValidator = $publicMethodValidator ?? new PublicMethodTestCoverageValidator(
            new SourceUnitTestFileResolver($srcMarker, $unitTestMarker),
        );
        $this->testMethodNameValidator = $testMethodNameValidator ?? new TestMethodNameValidator();
        $this->providerNameValidator = $providerNameValidator ?? new ProviderNameValidator();
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\ClassMethod>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassMethod::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        $file = $this->pathNormalizer->normalize($scope->getFile());

        if ($this->sourceFileMatcher->isSourceFile($file, $this->srcMarker)) {
            return $this->publicMethodValidator->errors($node, $file);
        }

        if ($this->testClassScope->isRestrictedTestClass($scope)) {
            $methodName = $node->name->toString();

            if (str_starts_with($methodName, 'test')) {
                return $this->testMethodNameValidator->errors($methodName, $node->getStartLine());
            }

            if (str_starts_with($methodName, 'provider')) {
                return $this->providerNameValidator->errors($methodName, $node->getStartLine());
            }
        }

        return [];
    }
}
