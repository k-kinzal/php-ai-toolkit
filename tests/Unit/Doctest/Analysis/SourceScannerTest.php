<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Analysis;

use PhpAiToolkit\Doctest\Analysis\ImportPreamble;
use PhpAiToolkit\Doctest\Analysis\PhpParserBridge;
use PhpAiToolkit\Doctest\Analysis\SourceScanner;
use PhpAiToolkit\Doctest\Analysis\Target;
use PhpAiToolkit\Doctest\DoctestException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SourceScanner::class)]
#[UsesClass(Target::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(ImportPreamble::class)]
final class SourceScannerTest extends TestCase
{
    public function testScanFindsEveryDocumentedDeclarationWithItsImports(): void
    {
        $targets = (new SourceScanner())->scan(__DIR__ . '/../../../Fixture/Doctest/project/src/Calculator.php', 'src/Calculator.php');

        self::assertSame(
            ['Tests\Fixture\Doctest\Project\Calculator', 'Tests\Fixture\Doctest\Project\Calculator::add()', 'Tests\Fixture\Doctest\Project\Calculator::divide()', 'Tests\Fixture\Doctest\Project\Calculator::printSum()', 'Tests\Fixture\Doctest\Project\Calculator::shape()'],
            array_map(static fn (Target $target): string => $target->symbol(), $targets),
        );
        self::assertSame(['use InvalidArgumentException;'], $targets[0]->imports);
        self::assertSame('src/Calculator.php', $targets[0]->reportPath());
    }

    public function testScanRejectsAFileItCannotRead(): void
    {
        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Could not read source file');

        (new SourceScanner())->scan(__DIR__ . '/does-not-exist.php');
    }

    public function testStatementsRejectsUnparsableSource(): void
    {
        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Could not parse /a.php');

        (new SourceScanner())->statements('/a.php', '<?php class {');
    }

    public function testFileTargetReadsALeadingDocblockAfterTheStrictTypesDeclaration(): void
    {
        $source = "<?php\n\ndeclare(strict_types=1);\n\n/**\n * File docs.\n */\n\nfunction helper(): void\n{\n}\n";

        $target = (new SourceScanner())->fileTarget('/app/helpers.php', $source, []);

        self::assertNotNull($target);
        self::assertSame(Target::FILE, $target->kind);
        self::assertSame("/**\n * File docs.\n */", $target->docComment);
        self::assertSame('helpers.php', $target->name);
    }

    public function testFileTargetIgnoresADocblockThatBelongsToADeclaration(): void
    {
        $source = "<?php\n\n/**\n * Class docs.\n */\nclass Thing\n{\n}\n";
        $declaration = new Target(Target::CLASS_LIKE, '/a.php', "/**\n * Class docs.\n */", 'Thing', 3);

        self::assertNull((new SourceScanner())->fileTarget('/a.php', $source, [$declaration]));
        self::assertNull((new SourceScanner())->fileTarget('/a.php', "<?php\nclass Thing {}\n", []));
    }

    public function testDeclarationsWalksNamespacesClassesFunctionsAndMethods(): void
    {
        $scanner = new SourceScanner();
        $source = "<?php\nnamespace App;\n/** Fn docs. */\nfunction helper(): void {}\n/** Class docs. */\nclass Thing { /** Method docs. */ public function run(): void {} }\n";

        $targets = $scanner->declarations($scanner->statements('/a.php', $source), '/a.php', '', null, []);

        self::assertSame(
            ['App\helper()', 'App\Thing', 'App\Thing::run()'],
            array_map(static fn (Target $target): string => $target->symbol(), $targets),
        );
    }

    public function testClassLikeTargetsSkipsAnonymousClasses(): void
    {
        $scanner = new SourceScanner();
        $statements = $scanner->statements('/a.php', "<?php\n\$thing = new class {};\n");
        $expression = $statements[0];

        self::assertInstanceOf(\PhpParser\Node\Stmt\Expression::class, $expression);
        self::assertSame([], $scanner->declarations($statements, '/a.php', '', null, []));
    }

    public function testNestedDeclarationsFindsCodeInsideControlFlow(): void
    {
        $scanner = new SourceScanner();
        $source = "<?php\nif (true) {\n    /** Nested docs. */\n    function nested(): void {}\n}\n";

        $targets = $scanner->declarations($scanner->statements('/a.php', $source), '/a.php', '', null, []);

        self::assertSame(['nested()'], array_map(static fn (Target $target): string => $target->symbol(), $targets));
    }

    public function testTargetReturnsNullForAnUndocumentedNode(): void
    {
        $scanner = new SourceScanner();
        $statements = $scanner->statements('/a.php', "<?php\nclass Thing {}\n");

        self::assertNull($scanner->target(Target::CLASS_LIKE, $statements[0], '/a.php', 'Thing', '', null, []));
    }
}
