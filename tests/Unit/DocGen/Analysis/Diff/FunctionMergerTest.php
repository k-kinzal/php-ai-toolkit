<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\ClassLikeMerger;
use Toolkit\DocGen\Analysis\Diff\DiffIndex;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\FunctionMerger;
use Toolkit\DocGen\Analysis\Diff\LcsMatcher;
use Toolkit\DocGen\Analysis\Diff\MemberMerger;
use Toolkit\DocGen\Analysis\Diff\ParameterMerger;
use Toolkit\DocGen\Analysis\Diff\SymbolFingerprint;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\DocTag;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
use Toolkit\DocGen\Analysis\Model\ParameterDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\Parse\AstParser;
use Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder;
use Toolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use Toolkit\DocGen\Analysis\Parse\FileSymbolCollector;
use Toolkit\DocGen\Analysis\Parse\FileSymbols;
use Toolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use Toolkit\DocGen\Analysis\Parse\ParameterModifiers;
use Toolkit\DocGen\Analysis\Parse\PhpParserBridge;
use Toolkit\DocGen\Analysis\Parse\SymbolContext;
use Toolkit\DocGen\Analysis\Parse\UseMapCollector;

/**
 * @covers \Toolkit\DocGen\Analysis\Diff\FunctionMerger
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Analysis\Diff\ClassLikeMerger
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Analysis\Model\DocTag
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \Toolkit\DocGen\Analysis\Diff\LcsMatcher
 * @uses \Toolkit\DocGen\Analysis\Diff\MemberMerger
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \Toolkit\DocGen\Analysis\Diff\ParameterMerger
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Analysis\Diff\SymbolFingerprint
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Parse\UseMapCollector
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
#[UsesClass(\Toolkit\Mutation\MutationContract::class)]
#[UsesClass(\Toolkit\Mutation\MutationContractReader::class)]
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
