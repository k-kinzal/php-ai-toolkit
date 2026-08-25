<?php

declare(strict_types=1);

namespace Toolkit\PhpUnit\TestReporter;

use PHPUnit\Event\Code\Test;

/**
 * Resolves display names from PHPUnit 10+ event test descriptors.
 */
final class EventTestNameResolver
{
    /**
     * Returns ClassName::methodName for test methods and the raw name otherwise.
     */
    public function resolve(Test $test): string
    {
        if ($test->isTestMethod()) {
            return $test->nameWithClass();
        }

        return $test->name();
    }
}
