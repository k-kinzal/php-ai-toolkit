<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Compare\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Compare\DiffIndex;
use Toolkit\DocGen\Compare\DiffKey;
use Toolkit\DocGen\Compare\DiffStatus;
use Toolkit\DocGen\Compare\Internal\ClassLikeMerger;
use Toolkit\DocGen\Compare\Internal\FunctionMerger;
use Toolkit\DocGen\Compare\Internal\MemberMerger;
use Toolkit\DocGen\Compare\Internal\ParameterMerger;
use Toolkit\DocGen\Compare\Internal\SymbolFingerprint;
use Toolkit\DocGen\Compare\LcsMatcher;
use Toolkit\DocGen\Parse\Internal\AstParser;
use Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder;
use Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Parse\Internal\ExprTextPrinter;
use Toolkit\DocGen\Parse\Internal\FileSymbolCollector;
use Toolkit\DocGen\Parse\Internal\NativeTypePrinter;
use Toolkit\DocGen\Parse\Internal\ParameterModifiers;
use Toolkit\DocGen\Parse\Internal\PhpParserBridge;
use Toolkit\DocGen\Parse\Internal\SymbolContext;
use Toolkit\DocGen\Parse\Internal\UseMapCollector;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\DocBlock;
use Toolkit\DocGen\Parse\Symbol\DocTag;
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\Symbol\FunctionDoc;
use Toolkit\DocGen\Parse\Symbol\ParameterDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;

/**
 * @covers \Toolkit\DocGen\Compare\Internal\FunctionMerger
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Compare\Internal\ClassLikeMerger
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Compare\DiffIndex
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Parse\Symbol\DocBlock
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Parse\Symbol\DocTag
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Internal\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\FunctionDoc
 * @uses \Toolkit\DocGen\Compare\LcsMatcher
 * @uses \Toolkit\DocGen\Compare\Internal\MemberMerger
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Compare\Internal\ParameterMerger
 * @uses \Toolkit\DocGen\Parse\Internal\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\PhpParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\SymbolContext
 * @uses \Toolkit\DocGen\Compare\Internal\SymbolFingerprint
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\Internal\UseMapCollector
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
#[UsesClass(\Toolkit\DocGen\Parse\Doc\MutationContract::class)]
#[UsesClass(\Toolkit\DocGen\Parse\Doc\MutationContractReader::class)]
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
