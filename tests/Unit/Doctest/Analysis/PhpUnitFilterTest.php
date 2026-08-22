<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Analysis;

use PhpAiToolkit\Doctest\Analysis\PhpUnitFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpUnitFilter::class)]
final class PhpUnitFilterTest extends TestCase
{
    public function testPatternQuotesTheIdentifierSoItMatchesItselfAndNothingElse(): void
    {
        $pattern = (new PhpUnitFilter())->pattern('App\Ledger::append()#2');

        self::assertSame('/App\\\\Ledger\:\:append\(\)\#2/', $pattern);
        self::assertSame(1, preg_match($pattern, 'Ledger::append() example #2 [App\Ledger::append()#2]'));
        self::assertSame(0, preg_match($pattern, 'Ledger::append() example #1 [App\Ledger::append()#1]'));
    }

    public function testCommandRunsThatOneExampleThroughPhpUnit(): void
    {
        self::assertSame(
            "vendor/bin/phpunit --filter '/App\\\\Ledger\:\:append\(\)\#2/'",
            (new PhpUnitFilter())->command('App\Ledger::append()#2'),
        );
    }
}
