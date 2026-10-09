<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Compare;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Compare\ClassLikeMerger;
use Toolkit\DocGen\Compare\DiffIndex;
use Toolkit\DocGen\Compare\DiffKey;
use Toolkit\DocGen\Compare\DiffStatus;
use Toolkit\DocGen\Compare\LcsMatcher;
use Toolkit\DocGen\Compare\MemberMerger;
use Toolkit\DocGen\Compare\ParameterMerger;
use Toolkit\DocGen\Compare\SymbolFingerprint;
use Toolkit\DocGen\Parse\AstParser;
use Toolkit\DocGen\Parse\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Parse\Builder\ConstantBuilder;
use Toolkit\DocGen\Parse\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Parse\Builder\FunctionBuilder;
use Toolkit\DocGen\Parse\Builder\MethodBuilder;
use Toolkit\DocGen\Parse\Builder\ParameterBuilder;
use Toolkit\DocGen\Parse\Builder\PropertyBuilder;
use Toolkit\DocGen\Parse\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Parse\ExprTextPrinter;
use Toolkit\DocGen\Parse\FileSymbolCollector;
use Toolkit\DocGen\Parse\NativeTypePrinter;
use Toolkit\DocGen\Parse\ParameterModifiers;
use Toolkit\DocGen\Parse\PhpParserBridge;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\ConstantDoc;
use Toolkit\DocGen\Parse\Symbol\DocBlock;
use Toolkit\DocGen\Parse\Symbol\DocTag;
use Toolkit\DocGen\Parse\Symbol\EnumCaseDoc;
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\Symbol\MethodDoc;
use Toolkit\DocGen\Parse\Symbol\ParameterDoc;
use Toolkit\DocGen\Parse\Symbol\PropertyDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;
use Toolkit\DocGen\Parse\SymbolContext;
use Toolkit\DocGen\Parse\UseMapCollector;

/**
 * @covers \Toolkit\DocGen\Compare\ClassLikeMerger
 * @uses \Toolkit\DocGen\Parse\AstParser
 * @uses \Toolkit\DocGen\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ConstantDoc
 * @uses \Toolkit\DocGen\Compare\DiffIndex
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Parse\Symbol\DocBlock
 * @uses \Toolkit\DocGen\Parse\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Parse\Symbol\DocTag
 * @uses \Toolkit\DocGen\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\EnumCaseDoc
 * @uses \Toolkit\DocGen\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Compare\LcsMatcher
 * @uses \Toolkit\DocGen\Compare\MemberMerger
 * @uses \Toolkit\DocGen\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Compare\ParameterMerger
 * @uses \Toolkit\DocGen\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\PropertyDoc
 * @uses \Toolkit\DocGen\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Compare\SymbolFingerprint
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\UseMapCollector
 */
#[CoversClass(ClassLikeMerger::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocTag::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(EnumCaseDoc::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(LcsMatcher::class)]
#[UsesClass(MemberMerger::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterMerger::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyDoc::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolFingerprint::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(\Toolkit\DocGen\Parse\Doc\MutationContract::class)]
#[UsesClass(\Toolkit\DocGen\Parse\Doc\MutationContractReader::class)]
final class ClassLikeMergerTest extends TestCase
{
    public function testMergeMarksTheDeclarationHeadTheMembersAndTheSymbolItself(): void
    {
        $base = (new FileSymbolCollector())->collect(
            (new AstParser())->parse(
                '<?php namespace Demo; class Engine { public const LIMIT = 3; public function __construct(int $count) {} public function stop(): void {} }',
                'src/Engine.php',
            ),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $head = (new FileSymbolCollector())->collect(
            (new AstParser())->parse(
                '<?php namespace Demo; final class Engine { public const LIMIT = 3; public function __construct(int $count, string $label) {} public function start(): void {} }',
                'src/Engine.php',
            ),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $index = new DiffIndex('main', 'HEAD');
        $keys = $index->keys();

        $merged = (new ClassLikeMerger())->merge($base, $head, $index);

        self::assertSame(DiffStatus::MODIFIED, $index->status($keys->header('Demo\Engine')));
        self::assertSame(DiffStatus::MODIFIED, $index->status($keys->classLike('Demo\Engine')));
        self::assertSame(DiffStatus::SAME, $index->status($keys->member('Demo\Engine', DiffKey::CONSTANT, 'LIMIT')));
        self::assertSame(DiffStatus::MODIFIED, $index->status($keys->member('Demo\Engine', DiffKey::METHOD, '__construct')));
        self::assertSame(DiffStatus::ADDED, $index->status($keys->member('Demo\Engine', DiffKey::METHOD, 'start')));
        self::assertSame(DiffStatus::REMOVED, $index->status($keys->member('Demo\Engine', DiffKey::METHOD, 'stop')));
        self::assertSame(
            DiffStatus::ADDED,
            $index->status($keys->parameter($keys->member('Demo\Engine', DiffKey::METHOD, '__construct'), 'label')),
        );
        self::assertSame(['__construct', 'stop', 'start'], [$merged->methods[0]->name, $merged->methods[1]->name, $merged->methods[2]->name]);
    }

    public function testMergeReportsASymbolNothingTouchedAsUnchanged(): void
    {
        $code = '<?php namespace Demo; class Engine { public function run(): void {} }';
        $base = (new FileSymbolCollector())->collect((new AstParser())->parse($code, 'src/Engine.php'), 'demo/pkg', 'src/Engine.php', false)->classLikes[0];
        $head = (new FileSymbolCollector())->collect((new AstParser())->parse($code, 'src/Engine.php'), 'demo/pkg', 'src/Engine.php', false)->classLikes[0];
        $index = new DiffIndex('main', 'HEAD');

        (new ClassLikeMerger())->merge($base, $head, $index);

        self::assertSame(DiffStatus::SAME, $index->status($index->keys()->classLike('Demo\Engine')));
    }

    public function testSingleMarksEveryPartOfASymbolOnlyOneRevisionHas(): void
    {
        $classLike = (new FileSymbolCollector())->collect(
            (new AstParser())->parse(
                '<?php namespace Demo; class Engine { public const LIMIT = 3; public int $count = 0; public function run(int $times): void {} }',
                'src/Engine.php',
            ),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $index = new DiffIndex('main', 'HEAD');
        $keys = $index->keys();

        $merged = (new ClassLikeMerger())->single($classLike, DiffStatus::ADDED, $index);

        self::assertSame(DiffStatus::ADDED, $index->status($keys->classLike('Demo\Engine')));
        self::assertSame(DiffStatus::ADDED, $index->status($keys->header('Demo\Engine')));
        self::assertSame(DiffStatus::ADDED, $index->status($keys->member('Demo\Engine', DiffKey::CONSTANT, 'LIMIT')));
        self::assertSame(DiffStatus::ADDED, $index->status($keys->member('Demo\Engine', DiffKey::PROPERTY, 'count')));
        self::assertSame(DiffStatus::ADDED, $index->status($keys->member('Demo\Engine', DiffKey::METHOD, 'run')));
        self::assertSame(
            DiffStatus::ADDED,
            $index->status($keys->parameter($keys->member('Demo\Engine', DiffKey::METHOD, 'run'), 'times')),
        );
        self::assertCount(1, $merged->methods);
    }

    public function testSingleMarksTheParametersOfARemovedSymbolAsRemoved(): void
    {
        $classLike = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { public function run(int $times): void {} }', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $index = new DiffIndex('main', 'HEAD');
        $keys = $index->keys();

        (new ClassLikeMerger())->single($classLike, DiffStatus::REMOVED, $index);

        self::assertSame(
            DiffStatus::REMOVED,
            $index->status($keys->parameter($keys->member('Demo\Engine', DiffKey::METHOD, 'run'), 'times')),
        );
    }

    public function testSingleMarksTheCasesOfAnEnumOnlyOneRevisionHas(): void
    {
        $classLike = (new FileSymbolCollector())->collect(
            (new AstParser())->parse("<?php namespace Demo; enum Status: string { case Active = 'active'; }", 'src/Status.php'),
            'demo/pkg',
            'src/Status.php',
            false,
        )->classLikes[0];
        $index = new DiffIndex('main', 'HEAD');

        (new ClassLikeMerger())->single($classLike, DiffStatus::ADDED, $index);

        self::assertSame(DiffStatus::ADDED, $index->status($index->keys()->member('Demo\Status', DiffKey::ENUM_CASE, 'Active')));
    }

    public function testStatusOfFoldsInTheStateOfEveryConstant(): void
    {
        $classLike = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { public const LIMIT = 3; }', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->member('Demo\Engine', DiffKey::CONSTANT, 'LIMIT'), DiffStatus::MODIFIED);

        self::assertSame(DiffStatus::MODIFIED, (new ClassLikeMerger())->statusOf($classLike, DiffStatus::SAME, $index));
    }

    public function testStatusOfCombinesTheHeadStateWithTheMemberStates(): void
    {
        $classLike = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { public function run(): void {} }', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $merger = new ClassLikeMerger();
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->member('Demo\Engine', DiffKey::METHOD, 'run'), DiffStatus::SAME);

        self::assertSame(DiffStatus::SAME, $merger->statusOf($classLike, DiffStatus::SAME, $index));
        self::assertSame(DiffStatus::MODIFIED, $merger->statusOf($classLike, DiffStatus::MODIFIED, $index));
    }

    public function testPropertiesAreMergedByNameAndKeepWhatTheHeadDropped(): void
    {
        $base = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { public int $kept = 0; public int $gone = 0; }', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $head = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { public int $kept = 1; }', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $index = new DiffIndex('main', 'HEAD');

        $properties = (new ClassLikeMerger())->properties($base, $head, $index);

        self::assertCount(2, $properties);
        self::assertSame(DiffStatus::MODIFIED, $index->status($index->keys()->member('Demo\Engine', DiffKey::PROPERTY, 'kept')));
        self::assertSame(DiffStatus::REMOVED, $index->status($index->keys()->member('Demo\Engine', DiffKey::PROPERTY, 'gone')));
    }

    public function testEnumCasesAreMergedByName(): void
    {
        $base = (new FileSymbolCollector())->collect(
            (new AstParser())->parse("<?php namespace Demo; enum Status: string { case Active = 'active'; case Gone = 'gone'; }", 'src/Status.php'),
            'demo/pkg',
            'src/Status.php',
            false,
        )->classLikes[0];
        $head = (new FileSymbolCollector())->collect(
            (new AstParser())->parse("<?php namespace Demo; enum Status: string { case Active = 'active'; case Fresh = 'fresh'; }", 'src/Status.php'),
            'demo/pkg',
            'src/Status.php',
            false,
        )->classLikes[0];
        $index = new DiffIndex('main', 'HEAD');

        $cases = (new ClassLikeMerger())->enumCases($base, $head, $index);

        self::assertCount(3, $cases);
        self::assertSame(DiffStatus::SAME, $index->status($index->keys()->member('Demo\Status', DiffKey::ENUM_CASE, 'Active')));
        self::assertSame(DiffStatus::ADDED, $index->status($index->keys()->member('Demo\Status', DiffKey::ENUM_CASE, 'Fresh')));
        self::assertSame(DiffStatus::REMOVED, $index->status($index->keys()->member('Demo\Status', DiffKey::ENUM_CASE, 'Gone')));
    }

    public function testConstantsAreMergedByNameWithTheirValues(): void
    {
        $base = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { public const A = 1; public int $kept = 0; public int $gone = 0; }', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $head = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { public const A = 2; public int $kept = 0; }', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $merger = new ClassLikeMerger();
        $index = new DiffIndex('main', 'HEAD');

        $constants = $merger->constants($base, $head, $index);
        $properties = $merger->properties($base, $head, $index);
        $cases = $merger->enumCases($base, $head, $index);

        self::assertCount(1, $constants);
        self::assertSame(DiffStatus::MODIFIED, $index->status($index->keys()->member('Demo\Engine', DiffKey::CONSTANT, 'A')));
        self::assertCount(2, $properties);
        self::assertSame(DiffStatus::REMOVED, $index->status($index->keys()->member('Demo\Engine', DiffKey::PROPERTY, 'gone')));
        self::assertSame([], $cases);
    }

    public function testMethodsMergeTheParameterListOfEveryMatchedMethod(): void
    {
        $base = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { public function run(int $times, string $label): void {} }', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $head = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { public function run(int $times): void {} }', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $index = new DiffIndex('main', 'HEAD');

        $methods = (new ClassLikeMerger())->methods($base, $head, $index);

        self::assertCount(1, $methods);
        self::assertCount(2, $methods[0]->parameters);
        self::assertSame(
            DiffStatus::REMOVED,
            $index->status($index->keys()->parameter($index->keys()->member('Demo\Engine', DiffKey::METHOD, 'run'), 'label')),
        );
    }

    public function testMergedMethodKeepsTheDeclarationAndReplacesTheParameters(): void
    {
        $head = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { final public static function run(int $times): int { return 1; } }', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $index = new DiffIndex('main', 'HEAD');

        $method = (new ClassLikeMerger())->mergedMethod(null, $head->methods[0], DiffStatus::ADDED, 'm:demo\engine::method.run', $index);

        self::assertSame('run', $method->name);
        self::assertTrue($method->isFinal);
        self::assertTrue($method->isStatic);
        self::assertSame($head->methods[0]->docBlock, $method->docBlock);
        self::assertCount(1, $method->parameters);
        self::assertSame(DiffStatus::ADDED, $index->status($index->keys()->parameter('m:demo\engine::method.run', 'times')));
    }

    public function testMergeMarksTheReturnTypeAndTheThrowsOfAChangedMethod(): void
    {
        $base = (new FileSymbolCollector())->collect(
            (new AstParser())->parse(
                '<?php namespace Demo; class Engine { /** @throws \RuntimeException on failure */ public function run(): int { return 1; } public function stop(): int { return 1; } }',
                'src/Engine.php',
            ),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $head = (new FileSymbolCollector())->collect(
            (new AstParser())->parse(
                '<?php namespace Demo; class Engine { /** @throws \LogicException on failure */ public function run(): ?int { return 1; } public function stop(): int { return 1; } }',
                'src/Engine.php',
            ),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];
        $index = new DiffIndex('main', 'HEAD');
        $keys = $index->keys();
        $changed = $keys->member('Demo\Engine', DiffKey::METHOD, 'run');
        $untouched = $keys->member('Demo\Engine', DiffKey::METHOD, 'stop');

        (new ClassLikeMerger())->merge($base, $head, $index);

        self::assertSame(DiffStatus::MODIFIED, $index->status($keys->returnType($changed)));
        self::assertSame(DiffStatus::MODIFIED, $index->status($keys->throwsTags($changed)));
        self::assertSame(DiffStatus::SAME, $index->status($keys->returnType($untouched)));
        self::assertSame(DiffStatus::SAME, $index->status($keys->throwsTags($untouched)));
    }

    public function testPartStatusFollowsTheMemberWhenTheMemberIsNewOrGone(): void
    {
        $merger = new ClassLikeMerger();

        self::assertSame(DiffStatus::ADDED, $merger->partStatus(null, 'int', DiffStatus::ADDED));
        self::assertSame(DiffStatus::REMOVED, $merger->partStatus('int', 'int', DiffStatus::REMOVED));
        self::assertSame(DiffStatus::SAME, $merger->partStatus('int', 'int', DiffStatus::MODIFIED));
        self::assertSame(DiffStatus::MODIFIED, $merger->partStatus('int', '?int', DiffStatus::MODIFIED));
        self::assertSame(DiffStatus::MODIFIED, $merger->partStatus(null, 'int', DiffStatus::SAME));
    }

    public function testRebuildKeepsTheIdentityOfTheSymbolAroundNewMemberLists(): void
    {
        $classLike = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { public const A = 1; public function run(): void {} }', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        )->classLikes[0];

        $rebuilt = (new ClassLikeMerger())->rebuild($classLike, [], [], [], []);

        self::assertSame('Demo\Engine', $rebuilt->fqcn);
        self::assertSame('src/Engine.php', $rebuilt->file);
        self::assertSame($classLike->useMap, $rebuilt->useMap);
        self::assertSame([], $rebuilt->constants);
        self::assertSame([], $rebuilt->methods);
    }
}
