<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Scanner;

use function array_map;
use function iterator_to_array;

use PhpAiToolkit\Doctest\Scanner\ParserFactoryBridge;
use PhpAiToolkit\Doctest\Scanner\SourceScanner;
use PhpAiToolkit\Doctest\Scanner\Target;
use PhpAiToolkit\Doctest\Scanner\TargetKind;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SourceScanner::class)]
#[UsesClass(Target::class)]
#[UsesClass(ParserFactoryBridge::class)]
final class SourceScannerTest extends TestCase
{
    public function testScanFileFindsTheDocumentedClassAndItsMethods(): void
    {
        $path = (string) realpath(__DIR__ . '/../../../Fixture/Doctest/project/src/Calculator.php');

        $targets = iterator_to_array((new SourceScanner())->scanFile($path), false);

        self::assertSame(
            [
                'Tests\Fixture\Doctest\Project\Calculator',
                'Tests\Fixture\Doctest\Project\Calculator::add()',
                'Tests\Fixture\Doctest\Project\Calculator::divide()',
                'Tests\Fixture\Doctest\Project\Calculator::printSum()',
                'Tests\Fixture\Doctest\Project\Calculator::shape()',
            ],
            array_map(static fn (Target $target): string => $target->getFullyQualifiedName(), $targets),
        );
    }

    public function testScanFileYieldsNothingForAMissingOrUnparsableFile(): void
    {
        $broken = sys_get_temp_dir() . '/doctest-source-scanner-broken.php';
        file_put_contents($broken, "<?php\nclass {\n");

        self::assertSame([], iterator_to_array((new SourceScanner())->scanFile('/does/not/exist.php'), false));
        self::assertSame([], iterator_to_array((new SourceScanner())->scanFile($broken), false));
    }

    public function testScanFileReportsAFileLevelDocblockFirst(): void
    {
        $path = sys_get_temp_dir() . '/doctest-source-scanner-file-doc.php';
        file_put_contents($path, "<?php\n\ndeclare(strict_types=1);\n\n/**\n * File docs.\n */\n\n/** Fn docs. */\nfunction doctestScannerHelper(): void\n{\n}\n");

        $targets = iterator_to_array((new SourceScanner())->scanFile($path), false);

        self::assertCount(2, $targets);
        self::assertSame(TargetKind::FILE, $targets[0]->type);
        self::assertSame("/**\n * File docs.\n */", $targets[0]->docblock);
        self::assertSame(TargetKind::FUNCTION, $targets[1]->type);
        self::assertSame('doctestScannerHelper', $targets[1]->name);
    }

    public function testExtractFileDocblockSkipsAStrictTypesDeclaration(): void
    {
        $scanner = new SourceScanner();

        self::assertSame('/** Docs. */', $scanner->extractFileDocblock("<?php\ndeclare(strict_types=1);\n/** Docs. */\nclass A {}"));
        self::assertSame('/** Docs. */', $scanner->extractFileDocblock("<?php\n/** Docs. */\nclass A {}"));
        self::assertNull($scanner->extractFileDocblock("<?php\nclass A {}"));
    }

    public function testTraverseAstFindsDeclarationsNestedInsideControlFlow(): void
    {
        $path = sys_get_temp_dir() . '/doctest-source-scanner-nested.php';
        file_put_contents($path, "<?php\nnamespace Probe;\nif (true) {\n    /** Nested docs. */\n    function doctestNested(): void {}\n}\n");

        $targets = iterator_to_array((new SourceScanner())->scanFile($path), false);

        self::assertSame(['Probe\doctestNested()'], array_map(static fn (Target $target): string => $target->getFullyQualifiedName(), $targets));
    }

    public function testTargetsOfSkipsAnonymousClassesAndUndocumentedDeclarations(): void
    {
        $path = sys_get_temp_dir() . '/doctest-source-scanner-anonymous.php';
        file_put_contents($path, "<?php\n\$thing = new class {\n    public function run(): void {}\n};\nclass Undocumented {}\n");

        self::assertSame([], iterator_to_array((new SourceScanner())->scanFile($path), false));
    }

    public function testClassTargetsReportsTheClassBeforeItsMethods(): void
    {
        $path = sys_get_temp_dir() . '/doctest-source-scanner-class.php';
        file_put_contents($path, "<?php\nnamespace Probe;\n/** Class docs. */\nclass Widget {\n    /** Method docs. */\n    public static function run(): void {}\n}\n");

        $targets = iterator_to_array((new SourceScanner())->scanFile($path), false);

        self::assertSame(['Probe\Widget', 'Probe\Widget::run()'], array_map(static fn (Target $target): string => $target->getFullyQualifiedName(), $targets));
        self::assertTrue($targets[1]->isStatic);
    }

    public function testMethodTargetIsSkippedOutsideAClass(): void
    {
        $scanner = new SourceScanner();
        $method = new \PhpParser\Node\Stmt\ClassMethod('run');

        self::assertSame([], iterator_to_array($scanner->methodTarget($method, '/a.php', null, null), false));
    }

    public function testFunctionTargetIsSkippedWithoutADocblock(): void
    {
        $scanner = new SourceScanner();
        $function = new \PhpParser\Node\Stmt\Function_('helper');

        self::assertSame([], iterator_to_array($scanner->functionTarget($function, '/a.php', null), false));
    }

    public function testChildrenReturnsTheSubNodesOfANode(): void
    {
        $scanner = new SourceScanner();
        $class = new \PhpParser\Node\Stmt\Class_('Widget', ['stmts' => [new \PhpParser\Node\Stmt\ClassMethod('run')]]);

        self::assertNotSame([], $scanner->children($class));
    }
}
