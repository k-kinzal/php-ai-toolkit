<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting\Filtering;

use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Reporting\Filtering\FindingFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Reporting\Filtering\FindingFilter
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Diagnostic\PolicyException
 */
#[CoversClass(FindingFilter::class)]
#[UsesClass(Finding::class)]
#[UsesClass(PolicyException::class)]
final class FindingFilterTest extends TestCase
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
        $this->expectException(PolicyException::class);
        new FindingFilter('', 'fatal');
    }
}
