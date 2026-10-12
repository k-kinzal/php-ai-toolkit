<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Executor;

use Throwable;

/**
 * What evaluating one piece of example code produced.
 *
 * Example code is arbitrary program text, so it either produces a value or
 * raises something. Both come back as this value rather than as a return and a
 * thrown exception, which keeps the one place that runs untrusted text — and so
 * the one broad catch doctest needs — inside ExpressionEvaluator.
 *
 * @template-covariant T = mixed
 * @property-read T|null $value
 * @property-read ?Throwable $error
 */
final class Evaluation
{
    /**
     * @param T|null $value the value the code produced, null when it raised
     * @param Throwable|null $error what the code raised, null when it completed
     */
    public function __construct(
        /** @readonly */
        private $value = null,
        /** @readonly */
        private ?Throwable $error = null,
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
            'value' => $this->value,
            'error' => $this->error,
            default => null,
        };
    }

    /**
     * Reports whether the code completed without raising.
     */
    public function completed(): bool
    {
        return $this->error === null;
    }
}
