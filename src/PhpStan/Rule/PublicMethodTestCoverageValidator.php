<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;

/**
 * Validates that each public source method has a matching unit test method.
 */
final class PublicMethodTestCoverageValidator
{
    /** @readonly */
    private SourceUnitTestFileResolver $testFileResolver;

    /** @readonly */
    private TestMethodFileReader $testMethodFileReader;

    /** @readonly */
    private PublicMethodTestCoverageErrorBuilder $errorBuilder;

    /**
     * Creates a public method test coverage validator.
     */
    public function __construct(
        ?SourceUnitTestFileResolver $testFileResolver = null,
        ?TestMethodFileReader $testMethodFileReader = null,
        ?PublicMethodTestCoverageErrorBuilder $errorBuilder = null,
    ) {
        $this->testFileResolver = $testFileResolver ?? new SourceUnitTestFileResolver();
        $this->testMethodFileReader = $testMethodFileReader ?? new TestMethodFileReader();
        $this->errorBuilder = $errorBuilder ?? new PublicMethodTestCoverageErrorBuilder();
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(\PhpParser\Node\Stmt\ClassMethod $node, string $sourceFile): array
    {
        if (!$node->isPublic() || $node->isAbstract()) {
            return [];
        }

        $methodName = $node->name->toString();

        if (str_starts_with($methodName, '__')) {
            return [];
        }

        $testFile = $this->testFileResolver->resolve($sourceFile);
        if ($testFile === null || !is_file($testFile)) {
            return [];
        }

        $expectedPrefix = 'test' . ucfirst($methodName);
        foreach ($this->testMethodFileReader->methodNames($testFile) as $testMethod) {
            if (str_starts_with($testMethod, $expectedPrefix)) {
                return [];
            }
        }

        return [$this->errorBuilder->build($methodName, $expectedPrefix, $node->getStartLine())];
    }
}
