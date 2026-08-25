<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Page\Component;

use function count;
use function sprintf;
use function strrchr;
use function substr;

use Toolkit\DocGen\Analysis\Reference\TestCase;
use Toolkit\DocGen\Render\RenderKit;

/**
 * Renders the test cases that exercise a symbol or member.
 *
 * The section answers what the code is guaranteed to do, so it is kept
 * apart from the call-site lists, which answer who depends on it.
 */
final class TestCaseHtml
{
    /**
     * Renders one collapsible test case section.
     *
     * @param list<TestCase> $testCases
     */
    public function section(RenderKit $services, string $pagePath, array $testCases): string
    {
        if ($testCases === []) {
            return '';
        }

        $html = '<details class="usage-details test-cases"><summary>'
            . sprintf('Test cases <span class="count">%d</span>', count($testCases))
            . '</summary><ul class="usage-list">';
        foreach ($testCases as $testCase) {
            $html .= '<li>' . $this->item($services, $pagePath, $testCase) . '</li>';
        }

        return $html . '</ul></details>' . "\n";
    }

    /**
     * Renders one expanded list of test cases.
     *
     * @param list<TestCase> $testCases
     */
    public function list(RenderKit $services, string $pagePath, array $testCases): string
    {
        if ($testCases === []) {
            return '';
        }

        $html = '<ul class="usage-list">';
        foreach ($testCases as $testCase) {
            $html .= '<li>' . $this->item($services, $pagePath, $testCase) . '</li>';
        }

        return $html . '</ul>' . "\n";
    }

    /**
     * Renders one labeled group of test cases inside a section.
     *
     * The group uses the same disclosure shape as the relation sections, so
     * every grouped list on a page is opened and closed the same way.
     *
     * @param list<TestCase> $testCases
     */
    public function subSection(RenderKit $services, string $pagePath, string $label, array $testCases, bool $open): string
    {
        if ($testCases === []) {
            return '';
        }

        return '<details class="usage-details test-cases"' . ($open ? ' open' : '') . '><summary>'
            . sprintf('%s <span class="count">%d</span>', $services->escaper->e($label), count($testCases))
            . '</summary>' . $this->list($services, $pagePath, $testCases) . '</details>' . "\n";
    }

    /**
     * Renders one test case entry linking to its source.
     */
    public function item(RenderKit $services, string $pagePath, TestCase $testCase): string
    {
        $escaper = $services->escaper;
        $tail = strrchr($testCase->testClass, '\\');
        $label = ($tail === false ? $testCase->testClass : substr($tail, 1))
            . ($testCase->testMethod !== null ? '::' . $testCase->testMethod : '');
        $name = sprintf('<code title="%s">%s</code>', $escaper->e($testCase->testClass), $escaper->e($label));
        if ($testCase->file !== null) {
            $href = $services->url->href($pagePath, $services->url->sourcePage($testCase->file));
            if ($testCase->line !== null) {
                $href .= '#L' . $testCase->line;
            }

            $name = sprintf('<a href="%s" title="%s"><code>%s</code></a>', $escaper->e($href), $escaper->e($testCase->testClass), $escaper->e($label));
        }

        return sprintf('%s <span class="usage-kind">%s</span>', $name, $escaper->e($this->originLabel($testCase->origin)));
    }

    /**
     * Returns the readable label of one evidence origin.
     */
    public function originLabel(string $origin): string
    {
        if ($origin === TestCase::ORIGIN_COVERAGE) {
            return 'covers';
        }

        if ($origin === TestCase::ORIGIN_CALL) {
            return 'calls';
        }

        return 'covers and calls';
    }
}
