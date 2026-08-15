<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Reference;

use function array_values;

use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageIndex;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;

use function strrpos;
use function strtolower;
use function substr;
use function usort;

/**
 * Query index over the test cases that exercise documented symbols.
 *
 * Two independent kinds of evidence are merged. Line coverage answers which
 * tests executed the lines of a symbol, which also covers symbols reached
 * indirectly. Call sites in dev sources answer which tests name the symbol,
 * which also covers symbols a test calls without producing line coverage,
 * for example when no coverage report is available at all or when the call
 * is the only thing a test does. A test found by both is reported once with
 * the combined origin.
 */
final class TestCaseIndex
{
    /** @var array<string, array<string, TestCase>> */
    private array $bySubject = [];

    /** @var array<string, string> */
    private array $testFiles = [];

    /** @var array<string, int> */
    private array $testLines = [];

    /**
     * Builds the index from usages, documented symbols and coverage.
     *
     * The symbols serve two purposes: dev classes among them supply the file
     * and line of every test method, and production classes among them
     * supply the line ranges that coverage is queried for. Passing no
     * coverage index restricts the result to call site evidence.
     *
     * @param list<Usage> $usages
     * @param list<ClassLikeDoc> $classLikes
     */
    public function build(array $usages, array $classLikes = [], ?CoverageIndex $coverage = null): void
    {
        foreach ($classLikes as $classLike) {
            $this->registerTestSymbol($classLike);
        }

        foreach ($usages as $usage) {
            $this->addCall($usage);
        }

        if ($coverage === null) {
            return;
        }

        foreach ($classLikes as $classLike) {
            $this->addCoverage($coverage, $classLike);
        }
    }

    /**
     * Registers the file and method lines of one dev class-like symbol.
     *
     * Coverage reports name a test by identifier only, so the position of a
     * test method is recovered from the parsed dev sources. Production
     * symbols are ignored, as they are never a test case themselves.
     */
    public function registerTestSymbol(ClassLikeDoc $classLike): void
    {
        if (!$classLike->isDev) {
            return;
        }

        $this->testFiles[strtolower($classLike->fqcn)] = $classLike->file;
        foreach ($classLike->methods as $method) {
            $this->testLines[strtolower($classLike->fqcn . '::' . $method->name)] = $method->startLine;
        }
    }

    /**
     * Records one usage made from a dev source as call site evidence.
     *
     * Usages from production sources and usages without a known origin class
     * carry no test information and are ignored. Every kind of reference is
     * accepted, because naming a symbol in a test, whether by calling it,
     * constructing it or mentioning it in a covers attribute, is evidence
     * that the test exercises it.
     */
    public function addCall(Usage $usage): void
    {
        if (!$usage->fromDev || $usage->fromFqcn === null) {
            return;
        }

        $this->testFiles[strtolower($usage->fromFqcn)] ??= $usage->file;
        $this->record($usage->targetFqcn, $usage->member, new TestCase(
            $usage->fromFqcn,
            $usage->fromMember,
            $usage->file,
            $usage->line,
            TestCase::ORIGIN_CALL,
        ));
    }

    /**
     * Records the coverage evidence of one class and each of its methods.
     *
     * Dev classes are skipped so that a test class is never reported as its
     * own test case.
     */
    public function addCoverage(CoverageIndex $coverage, ClassLikeDoc $classLike): void
    {
        if ($classLike->isDev) {
            return;
        }

        $this->addCoverageRange($coverage, $classLike->fqcn, null, $classLike->file, $classLike->startLine, $classLike->endLine);
        foreach ($classLike->methods as $method) {
            $this->addCoverageRange($coverage, $classLike->fqcn, $method->name, $classLike->file, $method->startLine, $method->endLine);
        }
    }

    /**
     * Records the tests covering one line range as evidence for a symbol.
     *
     * The covering test identifiers are split at their last "::" into the
     * test class and the test method; an identifier without a separator is
     * taken as a test class alone.
     */
    public function addCoverageRange(CoverageIndex $coverage, string $fqcn, ?string $member, string $file, int $startLine, int $endLine): void
    {
        foreach ($coverage->testsForRange($file, $startLine, $endLine) as $testId) {
            $separator = strrpos($testId, '::');
            $testClass = $separator === false ? $testId : substr($testId, 0, $separator);
            $testMethod = $separator === false ? '' : substr($testId, $separator + 2);
            if ($testClass === '') {
                continue;
            }

            $this->record($fqcn, $member, new TestCase(
                $testClass,
                $testMethod === '' ? null : $testMethod,
                $this->testFiles[strtolower($testClass)] ?? null,
                $this->testLines[strtolower($testId)] ?? null,
                TestCase::ORIGIN_COVERAGE,
            ));
        }
    }

    /**
     * Records one test case for a symbol and, when given, for one member.
     *
     * A member's evidence always counts for its class as well, so a class
     * page lists every test reaching any of its members.
     */
    public function record(string $fqcn, ?string $member, TestCase $testCase): void
    {
        $subjects = [strtolower($fqcn)];
        if ($member !== null) {
            $subjects[] = strtolower($fqcn . '::' . $member);
        }

        $identity = strtolower($testCase->testClass . '::' . ($testCase->testMethod ?? ''));
        foreach ($subjects as $subject) {
            $known = $this->bySubject[$subject][$identity] ?? null;
            $this->bySubject[$subject][$identity] = $known === null ? $testCase : $this->merge($known, $testCase);
        }
    }

    /**
     * Merges two records of the same test case into one.
     *
     * The first record wins on every position detail, because call sites are
     * recorded before coverage and point at the exact line of the reference,
     * while coverage only knows the test method's declaration. Differing
     * origins combine into the origin naming both.
     */
    public function merge(TestCase $known, TestCase $found): TestCase
    {
        return new TestCase(
            $known->testClass,
            $known->testMethod ?? $found->testMethod,
            $known->file ?? $found->file,
            $known->line ?? $found->line,
            $known->origin === $found->origin ? $known->origin : TestCase::ORIGIN_BOTH,
        );
    }

    /**
     * Returns the test cases exercising a class-like symbol.
     *
     * @return list<TestCase>
     */
    public function forType(string $fqcn): array
    {
        return $this->sorted($this->bySubject[strtolower($fqcn)] ?? []);
    }

    /**
     * Returns the test cases exercising one member of a symbol.
     *
     * @return list<TestCase>
     */
    public function forMember(string $fqcn, string $member): array
    {
        return $this->sorted($this->bySubject[strtolower($fqcn . '::' . $member)] ?? []);
    }

    /**
     * Sorts test cases by test class and then by test method.
     *
     * @param array<string, TestCase> $testCases
     *
     * @return list<TestCase>
     */
    public function sorted(array $testCases): array
    {
        $sorted = array_values($this->withoutClassLevelDuplicates($testCases));
        usort($sorted, static function (TestCase $a, TestCase $b): int {
            $byClass = strtolower($a->testClass) <=> strtolower($b->testClass);

            return $byClass === 0 ? strtolower($a->testMethod ?? '') <=> strtolower($b->testMethod ?? '') : $byClass;
        });

        return $sorted;
    }

    /**
     * Drops the class-level entry of a test class that named its methods.
     *
     * A test class is recorded without a method when the evidence comes from
     * a class-level reference, such as a covers attribute. That entry says
     * nothing beyond the method entries of the same class, so it is kept
     * only while no method of that class is known.
     *
     * @param array<string, TestCase> $testCases
     *
     * @return array<string, TestCase>
     */
    public function withoutClassLevelDuplicates(array $testCases): array
    {
        $withMethod = [];
        foreach ($testCases as $testCase) {
            if ($testCase->testMethod !== null) {
                $withMethod[strtolower($testCase->testClass)] = true;
            }
        }

        $kept = [];
        foreach ($testCases as $key => $testCase) {
            if ($testCase->testMethod !== null || !isset($withMethod[strtolower($testCase->testClass)])) {
                $kept[$key] = $testCase;
            }
        }

        return $kept;
    }
}
