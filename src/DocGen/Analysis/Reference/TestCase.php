<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Reference;

use function get_object_vars;

/**
 * One test case that exercises a documented class or member.
 *
 * A test case is evidence that the documented symbol behaves as described,
 * so it is modelled as a test identity rather than as a call site. The
 * origin records how the link was established: line coverage attributes the
 * symbol's own lines to a test, a call is a statically resolved reference to
 * the symbol from a dev source file, and both may hold at once.
 *
 * @property-read string $testClass
 * @property-read ?string $testMethod
 * @property-read ?string $file
 * @property-read ?int $line
 * @property-read string $origin
 */
final class TestCase
{
    /**
     * Origin of a test case established by line coverage alone.
     */
    public const ORIGIN_COVERAGE = 'coverage';

    /**
     * Origin of a test case established by a call site alone.
     */
    public const ORIGIN_CALL = 'call';

    /**
     * Origin of a test case established by both coverage and a call site.
     */
    public const ORIGIN_BOTH = 'coverage+call';

    /**
     * Creates one test case reference.
     *
     * The test method is null when the evidence names a test class without a
     * method. The file is null when no dev source revealed where the test
     * class lives, and the line is null when only coverage established the
     * link and the test method's own position is unknown.
     */
    public function __construct(
        /** @readonly */
        private string $testClass,
        /** @readonly */
        private ?string $testMethod,
        /** @readonly */
        private ?string $file,
        /** @readonly */
        private ?int $line,
        /** @readonly */
        private string $origin,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return get_object_vars($this)[$name] ?? null;
    }
}
