<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Executor;

use function implode;

use Toolkit\Doctest\Assertion\AssertionResult;
use Toolkit\Doctest\Parser\Example;

/**
 * Represents the result of executing an example.
 *
 * Contains the example that was executed, whether it passed all assertions,
 * and a list of any failures that occurred.
 *
 * @property-read Example $example
 * @property-read bool $passed
 * @property-read list<AssertionResult> $failures
 *
 * @example Creating a passing result
 *     $target = new \Toolkit\Doctest\Scanner\Target(
 *         \Toolkit\Doctest\Scanner\TargetKind::CLASS_LIKE,
 *         __FILE__,
 *         'docs',
 *         'Test',
 *         1,
 *     );
 *     $example = new \Toolkit\Doctest\Parser\Example('1+1', $target, 1, 0);
 *     $result = new \Toolkit\Doctest\Executor\ExecutionResult($example, true);
 *     $result->passed // => true
 *     $result->getErrorMessage() // => ''
 */
final class ExecutionResult
{
    /**
     * @param Example $example the example that was executed
     * @param bool $passed whether all assertions passed
     * @param list<AssertionResult> $failures list of failed assertions
     */
    public function __construct(
        /** @readonly */
        private Example $example,
        /** @readonly */
        private bool $passed,
        /** @readonly */
        private array $failures = [],
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'example' => $this->example,
            'passed' => $this->passed,
            'failures' => $this->failures,
            default => null,
        };
    }

    /**
     * Returns a formatted error message for all failures.
     *
     * Returns an empty string if the example passed.
     */
    public function getErrorMessage(): string
    {
        if ($this->passed) {
            return '';
        }

        $messages = [];
        foreach ($this->failures as $failure) {
            $messages[] = $failure->getDetailedMessage();
        }

        return implode("\n\n", $messages);
    }
}
