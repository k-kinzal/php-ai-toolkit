<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

/**
 * Resolves display names for PHPUnit test code objects.
 */
final class TestIssueNameResolver
{
    /**
     * Returns ClassName::methodName for test methods and the raw name otherwise.
     */
    public function resolve(\PHPUnit\Event\Code\Test $test): string
    {
        if ($test->isTestMethod()) {
            return $test->nameWithClass();
        }

        return $test->name();
    }
}
