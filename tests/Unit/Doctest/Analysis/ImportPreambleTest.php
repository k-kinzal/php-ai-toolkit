<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Analysis;

use PhpAiToolkit\Doctest\Analysis\ImportPreamble;
use PhpAiToolkit\Doctest\Analysis\PhpParserBridge;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImportPreamble::class)]
#[UsesClass(PhpParserBridge::class)]
final class ImportPreambleTest extends TestCase
{
    public function testRenderReturnsOneStatementPerImportAndIgnoresOtherCode(): void
    {
        $statements = (new PhpParserBridge())->parser()->parse("<?php\nuse App\\Money;\nuse function App\\sum;\n\$value = 1;\n") ?? [];

        self::assertSame(['use App\Money;', 'use function App\sum;'], (new ImportPreamble())->render($statements));
    }

    public function testUseStatementRendersAliasesAndSeveralNames(): void
    {
        $statements = (new PhpParserBridge())->parser()->parse("<?php\nuse App\\Money as Amount, App\\Ledger;\n") ?? [];
        $import = $statements[0];

        self::assertInstanceOf(\PhpParser\Node\Stmt\Use_::class, $import);
        self::assertSame('use App\Money as Amount, App\Ledger;', (new ImportPreamble())->useStatement($import));
    }

    public function testGroupUseStatementFlattensToOneImportPerName(): void
    {
        $statements = (new PhpParserBridge())->parser()->parse("<?php\nuse App\\{Money, Ledger as Book};\n") ?? [];
        $import = $statements[0];

        self::assertInstanceOf(\PhpParser\Node\Stmt\GroupUse::class, $import);
        self::assertSame("use App\Money;\nuse App\Ledger as Book;", (new ImportPreamble())->groupUseStatement($import));
    }

    public function testItemNameAppendsTheAliasOnlyWhenThereIsOne(): void
    {
        self::assertSame('App\Money', (new ImportPreamble())->itemName('App\Money', null));
        self::assertSame('App\Money as Amount', (new ImportPreamble())->itemName('App\Money', 'Amount'));
    }

    public function testKeywordNamesTheImportKind(): void
    {
        $preamble = new ImportPreamble();

        self::assertSame('', $preamble->keyword(\PhpParser\Node\Stmt\Use_::TYPE_NORMAL));
        self::assertSame('function ', $preamble->keyword(\PhpParser\Node\Stmt\Use_::TYPE_FUNCTION));
        self::assertSame('const ', $preamble->keyword(\PhpParser\Node\Stmt\Use_::TYPE_CONSTANT));
    }
}
