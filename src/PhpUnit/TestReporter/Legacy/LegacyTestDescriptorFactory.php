<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter\Legacy;

use function get_class;

use PHPUnit\Framework\TestCase;
use function preg_match;

use ReflectionException;
use ReflectionMethod;

/**
 * Builds a version-neutral descriptor from PHPUnit 9 test objects.
 */
final class LegacyTestDescriptorFactory
{
    /**
     * Extracts the test name and method source location when available.
     */
    public function fromTest(object $test): LegacyTestDescriptor
    {
        $name = $test instanceof TestCase ? $test->toString() : get_class($test);
        $file = '';
        $line = 0;

        if ($test instanceof TestCase && preg_match('/^(.+)::([^\s]+)(?: .*)?$/', $name, $matches) === 1) {
            try {
                $method = new ReflectionMethod($matches[1], $matches[2]);
                $file = (string) $method->getFileName();
                $line = $method->getStartLine();
                if ($line === false) {
                    $line = 0;
                }
            } catch (ReflectionException) {
                $file = '';
                $line = 0;
            }
        }

        return new LegacyTestDescriptor($name, $name, $file, $line);
    }
}
