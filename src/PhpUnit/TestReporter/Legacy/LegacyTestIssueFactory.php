<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter\Legacy;

use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueInput;
use PHPUnit\Framework\AssertionFailedError;
use Throwable;

/**
 * Converts PHPUnit 9 TestListener arguments into version-neutral issue inputs.
 */
final class LegacyTestIssueFactory
{
    /**
     * Creates the factory from legacy PHPUnit-specific collaborators.
     */
    public function __construct(
        /** @readonly */
        private ?LegacyTestDescriptorFactory $descriptorFactory = null,
        /** @readonly */
        private ?LegacyFailureDiffResolver $diffResolver = null,
    ) {
    }

    /**
     * Converts a failed-test listener callback into normalized issue data.
     */
    public function fromFailure(object $test, AssertionFailedError $failure): TestIssueInput
    {
        $descriptor = ($this->descriptorFactory ?? new LegacyTestDescriptorFactory())->fromTest($test);

        return new TestIssueInput(
            TestIssue::TYPE_FAILED,
            $descriptor->id,
            $descriptor->name,
            $descriptor->file,
            $descriptor->line,
            $failure->getMessage(),
            ($this->diffResolver ?? new LegacyFailureDiffResolver())->resolve($failure),
            $failure->getTraceAsString(),
        );
    }

    /**
     * Converts an errored-test listener callback into normalized issue data.
     */
    public function fromError(object $test, Throwable $throwable): TestIssueInput
    {
        $descriptor = ($this->descriptorFactory ?? new LegacyTestDescriptorFactory())->fromTest($test);

        return new TestIssueInput(
            TestIssue::TYPE_ERROR,
            $descriptor->id,
            $descriptor->name,
            $descriptor->file,
            $descriptor->line,
            $throwable->getMessage(),
            null,
            $throwable->getTraceAsString(),
        );
    }

    /**
     * Converts a risky-test listener callback into normalized issue data.
     */
    public function fromRisky(object $test, Throwable $throwable): TestIssueInput
    {
        $descriptor = ($this->descriptorFactory ?? new LegacyTestDescriptorFactory())->fromTest($test);

        return new TestIssueInput(
            TestIssue::TYPE_RISKY,
            $descriptor->id,
            $descriptor->name,
            $descriptor->file,
            $descriptor->line,
            $throwable->getMessage(),
        );
    }
}
