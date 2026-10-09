<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Compare\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Compare\Internal\SymbolFingerprint;
use Toolkit\DocGen\Parse\Doc\MutationContract;
use Toolkit\DocGen\Parse\Doc\MutationContractReader;
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
use Toolkit\DocGen\Parse\Symbol\ConstantDoc;
use Toolkit\DocGen\Parse\Symbol\DocBlock;
use Toolkit\DocGen\Parse\Symbol\DocTag;
use Toolkit\DocGen\Parse\Symbol\EnumCaseDoc;
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\Symbol\FunctionDoc;
use Toolkit\DocGen\Parse\Symbol\MethodDoc;
use Toolkit\DocGen\Parse\Symbol\ParameterDoc;
use Toolkit\DocGen\Parse\Symbol\PropertyDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;

/**
 * @covers \Toolkit\DocGen\Compare\Internal\SymbolFingerprint
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ConstantDoc
 * @uses \Toolkit\DocGen\Parse\Symbol\DocBlock
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Parse\Symbol\DocTag
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\EnumCaseDoc
 * @uses \Toolkit\DocGen\Parse\Internal\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Internal\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\FunctionDoc
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Parse\Internal\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\PhpParserBridge
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\PropertyDoc
 * @uses \Toolkit\DocGen\Parse\Internal\SymbolContext
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\Internal\UseMapCollector
 * @uses \Toolkit\DocGen\Parse\Doc\MutationContract
 * @uses \Toolkit\DocGen\Parse\Doc\MutationContractReader
 */
#[CoversClass(SymbolFingerprint::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocTag::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(EnumCaseDoc::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyDoc::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(MutationContract::class)]
#[UsesClass(MutationContractReader::class)]
final class SymbolFingerprintTest extends TestCase
{
    public function testClassHeaderCoversTheKeywordsAndTheParents(): void
    {
        $before = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine implements Runnable {}', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        );
        $after = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; final class Engine implements Runnable {}', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        );
        $fingerprint = new SymbolFingerprint();

        self::assertSame($fingerprint->classHeader($before->classLikes[0]), $fingerprint->classHeader($before->classLikes[0]));
        self::assertNotSame($fingerprint->classHeader($before->classLikes[0]), $fingerprint->classHeader($after->classLikes[0]));
    }

    public function testClassHeaderIgnoresTheMembersOfTheClass(): void
    {
        $before = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine {}', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        );
        $after = (new FileSymbolCollector())->collect(
            (new AstParser())->parse('<?php namespace Demo; class Engine { public function run(): void {} }', 'src/Engine.php'),
            'demo/pkg',
            'src/Engine.php',
            false,
        );
        $fingerprint = new SymbolFingerprint();

        self::assertSame($fingerprint->classHeader($before->classLikes[0]), $fingerprint->classHeader($after->classLikes[0]));
    }

    public function testMethodCoversTheSignatureTheModifiersAndTheDocumentation(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Engine
{
    /**
     * Runs once.
     */
    public function run(int $count): void
    {
    }

    public function run2(int $count): void
    {
    }

    public function run3(int $count, string $label): void
    {
    }

    final public function run4(int $count): void
    {
    }
}
PHP;
        $symbols = (new FileSymbolCollector())->collect((new AstParser())->parse($code, 'src/Engine.php'), 'demo/pkg', 'src/Engine.php', false);
        $methods = $symbols->classLikes[0]->methods;
        $fingerprint = new SymbolFingerprint();

        self::assertNotSame($fingerprint->method($methods[0]), $fingerprint->method($methods[1]));
        self::assertNotSame($fingerprint->method($methods[1]), $fingerprint->method($methods[2]));
        self::assertNotSame($fingerprint->method($methods[1]), $fingerprint->method($methods[3]));
    }

    public function testFunctionSymbolCoversTheParametersAndTheReturnType(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

function greet(string $name): string
{
    return $name;
}

function greet2(string $name): ?string
{
    return $name;
}
PHP;
        $symbols = (new FileSymbolCollector())->collect((new AstParser())->parse($code, 'src/functions.php'), 'demo/pkg', 'src/functions.php', false);
        $fingerprint = new SymbolFingerprint();

        self::assertNotSame($fingerprint->functionSymbol($symbols->functions[0]), $fingerprint->functionSymbol($symbols->functions[1]));
    }

    public function testPropertyCoversTheVisibilityTheTypeAndTheDefault(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Engine
{
    public int $count = 0;
    public int $count2 = 1;
    protected int $count3 = 0;
    public ?int $count4 = 0;
}
PHP;
        $properties = (new FileSymbolCollector())
            ->collect((new AstParser())->parse($code, 'src/Engine.php'), 'demo/pkg', 'src/Engine.php', false)
            ->classLikes[0]->properties;
        $fingerprint = new SymbolFingerprint();

        self::assertNotSame($fingerprint->property($properties[0]), $fingerprint->property($properties[1]));
        self::assertNotSame($fingerprint->property($properties[0]), $fingerprint->property($properties[2]));
        self::assertNotSame($fingerprint->property($properties[0]), $fingerprint->property($properties[3]));
    }

    public function testConstantCoversTheVisibilityAndTheValue(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Engine
{
    public const LIMIT = 3;
    public const LIMIT2 = 4;
    protected const LIMIT3 = 3;
}
PHP;
        $constants = (new FileSymbolCollector())
            ->collect((new AstParser())->parse($code, 'src/Engine.php'), 'demo/pkg', 'src/Engine.php', false)
            ->classLikes[0]->constants;
        $fingerprint = new SymbolFingerprint();

        self::assertNotSame($fingerprint->constant($constants[0]), $fingerprint->constant($constants[1]));
        self::assertNotSame($fingerprint->constant($constants[0]), $fingerprint->constant($constants[2]));
    }

    public function testEnumCaseCoversTheBackedValue(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

enum Status: string
{
    case Active = 'active';
    case Paused = 'active';
    case Stopped = 'stopped';
}
PHP;
        $cases = (new FileSymbolCollector())
            ->collect((new AstParser())->parse($code, 'src/Status.php'), 'demo/pkg', 'src/Status.php', false)
            ->classLikes[0]->enumCases;
        $fingerprint = new SymbolFingerprint();

        self::assertNotSame($fingerprint->enumCase($cases[0]), $fingerprint->enumCase($cases[1]));
        self::assertNotSame($fingerprint->enumCase($cases[0]), $fingerprint->enumCase($cases[2]));
    }

    public function testParameterCoversTheTypeTheDefaultAndTheDocumentedDescription(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Engine
{
    /**
     * @param int $count the run count
     */
    public function run(int $count, int $count2 = 1, string ...$rest): void
    {
    }

    /**
     * @param int $count how often to run
     */
    public function run2(int $count): void
    {
    }
}
PHP;
        $methods = (new FileSymbolCollector())
            ->collect((new AstParser())->parse($code, 'src/Engine.php'), 'demo/pkg', 'src/Engine.php', false)
            ->classLikes[0]->methods;
        $fingerprint = new SymbolFingerprint();

        self::assertNotSame($fingerprint->parameter($methods[0]->parameters[0]), $fingerprint->parameter($methods[0]->parameters[1]));
        self::assertNotSame($fingerprint->parameter($methods[0]->parameters[0]), $fingerprint->parameter($methods[0]->parameters[2]));
        self::assertNotSame($fingerprint->parameter($methods[0]->parameters[0]), $fingerprint->parameter($methods[1]->parameters[0]));
    }

    public function testParametersJoinsTheWholeListInDeclarationOrder(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Engine
{
    public function run(int $count, string $label): void
    {
    }

    public function run2(string $label, int $count): void
    {
    }
}
PHP;
        $methods = (new FileSymbolCollector())
            ->collect((new AstParser())->parse($code, 'src/Engine.php'), 'demo/pkg', 'src/Engine.php', false)
            ->classLikes[0]->methods;
        $fingerprint = new SymbolFingerprint();

        self::assertSame('', $fingerprint->parameters([]));
        self::assertNotSame($fingerprint->parameters($methods[0]->parameters), $fingerprint->parameters($methods[1]->parameters));
    }

    public function testParameterTreatsMutationPermissionAsAnApiChange(): void
    {
        $type = new TypeSignature('object', null);
        $readonly = new ParameterDoc('value', $type, false, false, null, null, '', false);
        $mutable = new ParameterDoc('value', $type, false, false, null, null, '', true);

        self::assertNotSame(
            (new SymbolFingerprint())->parameter($readonly),
            (new SymbolFingerprint())->parameter($mutable),
        );
    }

    public function testTypeCoversTheDeclaredTypeAndItsDocumentedRefinement(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Engine
{
    /**
     * @return list<int> the counts
     */
    public function counts(): array
    {
        return [];
    }

    /**
     * @return list<string> the counts
     */
    public function counts2(): array
    {
        return [];
    }

    public function counts3(): array
    {
        return [];
    }
}
PHP;
        $methods = (new FileSymbolCollector())
            ->collect((new AstParser())->parse($code, 'src/Engine.php'), 'demo/pkg', 'src/Engine.php', false)
            ->classLikes[0]->methods;
        $fingerprint = new SymbolFingerprint();

        self::assertSame('array', $fingerprint->type($methods[2]->returnType));
        self::assertNotSame($fingerprint->type($methods[0]->returnType), $fingerprint->type($methods[1]->returnType));
        self::assertNotSame($fingerprint->type($methods[0]->returnType), $fingerprint->type($methods[2]->returnType));
    }

    public function testThrowsTagsCoverTheTypeAndTheDescriptionOfEveryTag(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Engine
{
    /**
     * @throws \RuntimeException when the count is negative
     */
    public function run(): void
    {
    }

    /**
     * @throws \RuntimeException when the count is zero
     */
    public function run2(): void
    {
    }

    /**
     * @throws \LogicException when the count is negative
     */
    public function run3(): void
    {
    }

    public function run4(): void
    {
    }
}
PHP;
        $methods = (new FileSymbolCollector())
            ->collect((new AstParser())->parse($code, 'src/Engine.php'), 'demo/pkg', 'src/Engine.php', false)
            ->classLikes[0]->methods;
        $fingerprint = new SymbolFingerprint();

        self::assertSame('', $fingerprint->throwsTags(null));
        self::assertSame('', $fingerprint->throwsTags($methods[3]->docBlock));
        self::assertNotSame($fingerprint->throwsTags($methods[0]->docBlock), $fingerprint->throwsTags($methods[1]->docBlock));
        self::assertNotSame($fingerprint->throwsTags($methods[0]->docBlock), $fingerprint->throwsTags($methods[2]->docBlock));
    }

    public function testDocBlockComparesTheWordsAndNotTheLineBreaks(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Engine
{
    /**
     * Runs the engine once.
     */
    public function run(): void
    {
    }

    /**
     * Runs the engine
     * once.
     */
    public function run2(): void
    {
    }

    /**
     * Runs the engine twice.
     */
    public function run3(): void
    {
    }
}
PHP;
        $methods = (new FileSymbolCollector())
            ->collect((new AstParser())->parse($code, 'src/Engine.php'), 'demo/pkg', 'src/Engine.php', false)
            ->classLikes[0]->methods;
        $fingerprint = new SymbolFingerprint();

        self::assertSame('', $fingerprint->docBlock(null));
        self::assertSame($fingerprint->docBlock($methods[0]->docBlock), $fingerprint->docBlock($methods[1]->docBlock));
        self::assertNotSame($fingerprint->docBlock($methods[0]->docBlock), $fingerprint->docBlock($methods[2]->docBlock));
    }
}
