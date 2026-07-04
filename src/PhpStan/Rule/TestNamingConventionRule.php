<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final class TestNamingConventionRule implements Rule
{
    /** @var array<string, list<string>> */
    private static array $testMethodCache = [];

    /**
     * @param TestClassScope $testClassScope test class scope detector
     * @param string $srcMarker path marker identifying source directories
     * @param string $unitTestMarker path marker identifying unit test directories
     */
    public function __construct(
        private readonly TestClassScope $testClassScope,
        private readonly string $srcMarker = '/src/',
        private readonly string $unitTestMarker = '/tests/Unit/',
    ) {
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
        $file = str_replace('\\', '/', $scope->getFile());

        if ($this->isSourceFile($file)) {
            return $this->validatePublicMethodHasTest($node, $file);
        }

        if ($this->testClassScope->isRestrictedTestClass($scope)) {
            $methodName = $node->name->toString();

            if (str_starts_with($methodName, 'test')) {
                return $this->validateTestMethodName($methodName, $node->getStartLine());
            }

            if (str_starts_with($methodName, 'provider')) {
                return $this->validateProviderName($methodName, $node->getStartLine());
            }
        }

        return [];
    }

    private function isSourceFile(string $file): bool
    {
        return strpos($file, $this->srcMarker) !== false;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validatePublicMethodHasTest(\PhpParser\Node\Stmt\ClassMethod $node, string $sourceFile): array
    {
        if (!$node->isPublic() || $node->isAbstract()) {
            return [];
        }

        $methodName = $node->name->toString();

        if (str_starts_with($methodName, '__')) {
            return [];
        }

        $testFile = $this->computeTestFilePath($sourceFile);
        if ($testFile === null || !is_file($testFile)) {
            return [];
        }

        $testMethods = $this->getTestMethodsFromFile($testFile);
        $expectedPrefix = 'test' . ucfirst($methodName);

        foreach ($testMethods as $testMethod) {
            if (str_starts_with($testMethod, $expectedPrefix)) {
                return [];
            }
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Public method %s() has no corresponding test method starting with %s() in the unit test file. Each public method must have at least one test that verifies its behavior.',
                    $methodName,
                    $expectedPrefix
                )
            )
                ->identifier('customRules.publicMethodWithoutTest')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function computeTestFilePath(string $sourceFile): ?string
    {
        $pos = strpos($sourceFile, $this->srcMarker);
        if ($pos === false) {
            return null;
        }

        $root = substr($sourceFile, 0, $pos);
        $relativePath = substr($sourceFile, $pos + strlen($this->srcMarker));
        $testRelativePath = substr($relativePath, 0, -4) . 'Test.php';

        return $root . $this->unitTestMarker . $testRelativePath;
    }

    /**
     * @return list<string>
     */
    private function getTestMethodsFromFile(string $testFile): array
    {
        if (isset(self::$testMethodCache[$testFile])) {
            return self::$testMethodCache[$testFile];
        }

        $content = file_get_contents($testFile);
        if ($content === false) {
            return self::$testMethodCache[$testFile] = [];
        }

        preg_match_all('/function\s+(test[A-Z]\w*)\s*\(/', $content, $matches);

        return self::$testMethodCache[$testFile] = $matches[1];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateTestMethodName(string $methodName, int $line): array
    {
        $suffix = substr($methodName, 4);

        if ($suffix === '') {
            return [
                RuleErrorBuilder::message(
                    'Test method test() must follow the pattern test[MethodName] or test[MethodName][Behavior]. The prefix "test" alone is not a valid name. Example: testUserCanLogin().'
                )
                    ->identifier('customRules.testMethodNamingConvention')
                    ->line($line)
                    ->build(),
            ];
        }

        if (preg_match('/^[A-Z]$/', $suffix[0]) !== 1) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Test method %s() does not follow the naming convention. After the "test" prefix, the next character must be an uppercase letter (PascalCase). Example: testSomething(), testUserCanLogin().',
                        $methodName
                    )
                )
                    ->identifier('customRules.testMethodNamingConvention')
                    ->line($line)
                    ->build(),
            ];
        }

        if (preg_match('/^(Construct|Destruct)/', $suffix) === 1) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Test method %s() tests a constructor or destructor directly. Constructors and destructors are implementation details; test the resulting behavior through the public API instead.',
                        $methodName
                    )
                )
                    ->identifier('customRules.testMethodProhibitedConstructorDestructor')
                    ->line($line)
                    ->build(),
            ];
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateProviderName(string $methodName, int $line): array
    {
        $suffix = substr($methodName, 8);

        if ($suffix === '') {
            return [
                RuleErrorBuilder::message(
                    'Data provider provider() must follow the pattern provider[TestCaseName]. The prefix "provider" alone is not a valid name. Example: providerValidEmails().'
                )
                    ->identifier('customRules.providerNamingConvention')
                    ->line($line)
                    ->build(),
            ];
        }

        if (preg_match('/^[A-Z]$/', $suffix[0]) !== 1) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Data provider %s() does not follow the naming convention. After the "provider" prefix, the next character must be an uppercase letter (PascalCase). Example: providerValidEmails(), providerUserData().',
                        $methodName
                    )
                )
                    ->identifier('customRules.providerNamingConvention')
                    ->line($line)
                    ->build(),
            ];
        }

        return [];
    }
}
