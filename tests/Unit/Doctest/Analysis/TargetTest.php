<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Analysis;

use PhpAiToolkit\Doctest\Analysis\Target;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Target::class)]
final class TargetTest extends TestCase
{
    public function testSymbolSpellsEachKindTheWayCodeNamesIt(): void
    {
        self::assertSame('/app/src/helpers.php', (new Target(Target::FILE, '/app/src/helpers.php', '/** */', 'helpers.php', 1))->symbol());
        self::assertSame('App\Ledger', (new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4, 'App'))->symbol());
        self::assertSame('App\append()', (new Target(Target::FUNCTION, '/a.php', '/** */', 'append', 4, 'App'))->symbol());
        self::assertSame('App\Ledger::append()', (new Target(Target::METHOD, '/a.php', '/** */', 'append', 4, 'App', 'Ledger'))->symbol());
        self::assertSame('Ledger', (new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4))->symbol());
    }

    public function testShortNameDropsTheNamespace(): void
    {
        self::assertSame('helpers.php', (new Target(Target::FILE, '/app/src/helpers.php', '/** */', 'helpers.php', 1))->shortName());
        self::assertSame('Ledger', (new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4, 'App'))->shortName());
        self::assertSame('append()', (new Target(Target::FUNCTION, '/a.php', '/** */', 'append', 4, 'App'))->shortName());
        self::assertSame('Ledger::append()', (new Target(Target::METHOD, '/a.php', '/** */', 'append', 4, 'App', 'Ledger'))->shortName());
    }

    public function testPreambleReplaysTheNamespaceAndImports(): void
    {
        $target = new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4, 'App\Billing', null, ['use App\Money;', 'use function count;']);

        self::assertSame("namespace App\Billing;\nuse App\Money;\nuse function count;\n", $target->preamble());
    }

    public function testPreambleIsEmptyForAGlobalFileWithoutImports(): void
    {
        self::assertSame('', (new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4))->preamble());
    }

    public function testReportPathPrefersTheDisplayPath(): void
    {
        $target = new Target(Target::CLASS_LIKE, '/abs/src/Ledger.php', '/** */', 'Ledger', 4, 'App', null, [], 'src/Ledger.php');

        self::assertSame('src/Ledger.php', $target->reportPath());
        self::assertSame('/abs/src/Ledger.php', $target->path);
        self::assertSame('/abs/src/Ledger.php', (new Target(Target::CLASS_LIKE, '/abs/src/Ledger.php', '/** */', 'Ledger', 4))->reportPath());
    }

    public function testExposesTheDeclarationItWasBuiltFrom(): void
    {
        $target = new Target(Target::METHOD, '/a.php', '/** doc */', 'append', 12, 'App', 'Ledger', ['use App\Money;'], 'a.php');

        self::assertSame(Target::METHOD, $target->kind);
        self::assertSame('/** doc */', $target->docComment);
        self::assertSame('append', $target->name);
        self::assertSame(12, $target->line);
        self::assertSame('App', $target->namespace);
        self::assertSame('Ledger', $target->className);
        self::assertSame(['use App\Money;'], $target->imports);
        self::assertSame('a.php', $target->displayPath);
    }
}
