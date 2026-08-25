<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PhpAiToolkit\DocGen\Analysis\Diff\ClassLikeMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\FunctionMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\LcsMatcher;
use PhpAiToolkit\DocGen\Analysis\Diff\MemberMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\ParameterMerger;
use PhpAiToolkit\DocGen\Analysis\Diff\SymbolFingerprint;
use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Analysis\Model\DocTag;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\MethodBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbolCollector;
use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbols;
use PhpAiToolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterModifiers;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\DocGen\Analysis\Parse\SymbolContext;
use PhpAiToolkit\DocGen\Analysis\Parse\UseMapCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Diff\FunctionMerger
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\AstParser
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\ClassLikeMerger
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\DocBlock
 * @uses \PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\DocTag
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\LcsMatcher
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\MemberMerger
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\ParameterMerger
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\SymbolFingerprint
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \PhpAiToolkit\DocGen\Analysis\Parse\UseMapCollector
 */
#[CoversClass(FunctionMerger::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeMerger::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocTag::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(LcsMatcher::class)]
#[UsesClass(MemberMerger::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterMerger::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolFingerprint::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UseMapCollector::class)]
final class FunctionMergerTest extends TestCase
{
    public function testMergeMarksAChangedFunctionAndItsNewParameter(): void
    {
        $base = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; function greet(string $name): string { return $name; }', 'src/functions.php'),
            'demo/pkg',
            'src/functions.php',
            false,
        )->functions[0];
        $head = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; function greet(string $name, string $greeting): string { return $name; }', 'src/functions.php'),
            'demo/pkg',
            'src/functions.php',
            false,
        )->functions[0];
        $index = new DiffIndex('main', 'HEAD');
        $key = $index->keys()->functionSymbol('Demo\greet');

        $merged = (new FunctionMerger())->merge($base, $head, $index);

        self::assertSame(DiffStatus::MODIFIED, $index->status($key));
        self::assertSame(DiffStatus::ADDED, $index->status($index->keys()->parameter($key, 'greeting')));
        self::assertCount(2, $merged->parameters);
    }

    public function testMergeReportsAnUntouchedFunctionAsUnchanged(): void
    {
        $code = '<?php namespace Demo; function greet(string $name): string { return $name; }';
        $base = (new FileSymbolCollector())->collect((new AstParser())->parse($code, 'src/functions.php'), 'demo/pkg', 'src/functions.php', false)->functions[0];
        $head = (new FileSymbolCollector())->collect((new AstParser())->parse($code, 'src/functions.php'), 'demo/pkg', 'src/functions.php', false)->functions[0];
        $index = new DiffIndex('main', 'HEAD');

        (new FunctionMerger())->merge($base, $head, $index);

        self::assertSame(DiffStatus::SAME, $index->status($index->keys()->functionSymbol('Demo\greet')));
    }

    public function testSingleMarksAFunctionOnlyOneRevisionHasWithItsParameters(): void
    {
        $function = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; function greet(string $name): string { return $name; }', 'src/functions.php'),
            'demo/pkg',
            'src/functions.php',
            false,
        )->functions[0];
        $index = new DiffIndex('main', 'HEAD');
        $key = $index->keys()->functionSymbol('Demo\greet');

        (new FunctionMerger())->single($function, DiffStatus::REMOVED, $index);

        self::assertSame(DiffStatus::REMOVED, $index->status($key));
        self::assertSame(DiffStatus::REMOVED, $index->status($index->keys()->parameter($key, 'name')));
    }

    public function testSingleMarksTheParametersOfAnAddedFunctionAsAdded(): void
    {
        $function = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; function greet(string $name): string { return $name; }', 'src/functions.php'),
            'demo/pkg',
            'src/functions.php',
            false,
        )->functions[0];
        $index = new DiffIndex('main', 'HEAD');
        $key = $index->keys()->functionSymbol('Demo\greet');

        (new FunctionMerger())->single($function, DiffStatus::ADDED, $index);

        self::assertSame(DiffStatus::ADDED, $index->status($index->keys()->parameter($key, 'name')));
    }

    public function testMarkPartsRecordsTheReturnTypeAndTheThrowsOfAFunction(): void
    {
        $base = (new FileSymbolCollector())->collect(
            (new AstParser())->parse(
                '<?php namespace Demo; /** @throws \RuntimeException on failure */ function greet(string $name): string { return $name; }',
                'src/functions.php',
            ),
            'demo/pkg',
            'src/functions.php',
            false,
        )->functions[0];
        $head = (new FileSymbolCollector())->collect(
            (new AstParser())->parse(
                '<?php namespace Demo; /** @throws \RuntimeException on failure */ function greet(string $name): ?string { return $name; }',
                'src/functions.php',
            ),
            'demo/pkg',
            'src/functions.php',
            false,
        )->functions[0];
        $index = new DiffIndex('main', 'HEAD');
        $key = $index->keys()->functionSymbol('Demo\greet');

        (new FunctionMerger())->markParts($base, $head, DiffStatus::MODIFIED, $key, $index);

        self::assertSame(DiffStatus::MODIFIED, $index->status($index->keys()->returnType($key)));
        self::assertSame(DiffStatus::SAME, $index->status($index->keys()->throwsTags($key)));
    }

    public function testMergeMarksTheReturnTypeOfAChangedFunction(): void
    {
        $base = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; function greet(string $name): string { return $name; }', 'src/functions.php'),
            'demo/pkg',
            'src/functions.php',
            false,
        )->functions[0];
        $head = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; function greet(string $name): ?string { return $name; }', 'src/functions.php'),
            'demo/pkg',
            'src/functions.php',
            false,
        )->functions[0];
        $index = new DiffIndex('main', 'HEAD');
        $key = $index->keys()->functionSymbol('Demo\greet');

        (new FunctionMerger())->merge($base, $head, $index);

        self::assertSame(DiffStatus::MODIFIED, $index->status($index->keys()->returnType($key)));
    }

    public function testRebuildKeepsTheIdentityOfTheFunctionAroundANewParameterList(): void
    {
        $function = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; function greet(string $name): string { return $name; }', 'src/functions.php'),
            'demo/pkg',
            'src/functions.php',
            false,
        )->functions[0];

        $rebuilt = (new FunctionMerger())->rebuild($function, []);

        self::assertSame('Demo\greet', $rebuilt->fqn);
        self::assertSame('src/functions.php', $rebuilt->file);
        self::assertSame([], $rebuilt->parameters);
        self::assertSame($function->returnType, $rebuilt->returnType);
    }
}
