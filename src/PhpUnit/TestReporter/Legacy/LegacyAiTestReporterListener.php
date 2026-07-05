<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter\Legacy;

use function getenv;
use function max;

use PhpAiToolkit\PhpUnit\TestReporter\TestReporterRuntime;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use Throwable;

/**
 * PHPUnit 9 listener adapter for the shared TestReporter runtime.
 */
final class LegacyAiTestReporterListener implements TestListener
{
    private TestReporterRuntime $runtime;

    private LegacyTestIssueFactory $factory;

    private int $suiteDepth = 0;

    /**
     * Creates the PHPUnit 9 listener adapter around the shared runtime.
     *
     * @param TestReporterRuntime|null $runtime shared reporter runtime, or null to build one from the current process
     * @param LegacyTestIssueFactory|null $factory legacy callback converter, or null to use the default converter
     */
    public function __construct(?TestReporterRuntime $runtime = null, ?LegacyTestIssueFactory $factory = null)
    {
        $this->runtime = $runtime ?? TestReporterRuntime::fromCurrentProcess();
        $this->factory = $factory ?? new LegacyTestIssueFactory();
    }

    /**
     * Records an unexpected test error reported by PHPUnit 9.
     */
    public function addError(Test $test, Throwable $t, float $time): void
    {
        if (getenv('PARATEST') !== false) {
            return;
        }

        $this->runtime->collector()->record($this->factory->fromError($test, $t));
    }

    /**
     * Ignores PHPUnit 9 warnings because the reporter only mirrors failures, errors, and risky tests.
     */
    public function addWarning(Test $test, Warning $e, float $time): void
    {
    }

    /**
     * Records an assertion failure reported by PHPUnit 9.
     */
    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
        if (getenv('PARATEST') !== false) {
            return;
        }

        $this->runtime->collector()->record($this->factory->fromFailure($test, $e));
    }

    /**
     * Ignores incomplete tests to keep behavior aligned with the PHPUnit 10+ adapter.
     */
    public function addIncompleteTest(Test $test, Throwable $t, float $time): void
    {
    }

    /**
     * Records a risky test reported by PHPUnit 9.
     */
    public function addRiskyTest(Test $test, Throwable $t, float $time): void
    {
        if (getenv('PARATEST') !== false) {
            return;
        }

        $this->runtime->collector()->record($this->factory->fromRisky($test, $t));
    }

    /**
     * Ignores skipped tests to keep behavior aligned with the PHPUnit 10+ adapter.
     */
    public function addSkippedTest(Test $test, Throwable $t, float $time): void
    {
    }

    /**
     * Tracks nested suite depth so final output is written once.
     */
    public function startTestSuite(TestSuite $suite): void
    {
        $this->suiteDepth++;
    }

    /**
     * Writes the shared reporter output after the root suite completes.
     */
    public function endTestSuite(TestSuite $suite): void
    {
        $this->suiteDepth = max(0, $this->suiteDepth - 1);

        if ($this->suiteDepth === 0 && getenv('PARATEST') === false) {
            $this->runtime->writeReport();
        }
    }

    /**
     * Ignores test start notifications because issue collection happens on terminal callbacks.
     */
    public function startTest(Test $test): void
    {
    }

    /**
     * Ignores test end notifications because issue collection happens on terminal callbacks.
     */
    public function endTest(Test $test, float $time): void
    {
    }
}
