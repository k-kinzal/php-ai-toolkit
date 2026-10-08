<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting\Filtering;

use Guard\Diagnostic\Finding;
use Guard\Reporting\Filtering\FindingFilter;

/**
 * @covers \Guard\Reporting\Filtering\FindingFilter
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Diagnostic\PolicyException
 */
#[\PHPUnit\Framework\Attributes\CoversClass(FindingFilter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Finding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\PolicyException::class)]
final class FindingFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testSelectCombinesFixabilityLevelAndLiteralCaseInsensitiveQuery(): void
    {
        $manual = new Finding('src/A.php', 'metrics.file_lines', 'required', 'Too large. Split it.');
        $warning = new Finding('a.json', 'config.value', 'recommended', 'Wrong value. Set /x to 2.', true);
        $error = new Finding('b.json', 'config.other', 'required', 'Wrong value. Set /x to 2.', true);
        $all = [$manual, $warning, $error];
        self::assertSame([$warning], (new FindingFilter('VALUE', 'warning', true))->select($all));
        self::assertSame([$warning, $error], (new FindingFilter('', null, true))->select($all));
        self::assertSame([$manual], (new FindingFilter('src/a.php'))->select($all));
        self::assertSame([], (new FindingFilter('.*'))->select($all));
        self::assertSame($all, (new FindingFilter())->select($all));
    }

    public function testSelectRejectsUnknownSeverityInsteadOfSilentlyHidingFindings(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        new FindingFilter('', 'fatal');
    }
}
