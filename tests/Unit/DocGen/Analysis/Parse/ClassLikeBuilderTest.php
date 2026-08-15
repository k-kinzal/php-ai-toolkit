<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse;

use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc;
use PhpAiToolkit\DocGen\Analysis\Model\EnumCaseDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc;
use PhpAiToolkit\DocGen\Analysis\Model\PropertyDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\ClassLikeBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ConstantBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\EnumCaseBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\MethodBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterModifiers;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\DocGen\Analysis\Parse\PropertyBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\SymbolContext;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassLikeBuilder::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(EnumCaseDoc::class)]
#[UsesClass(ExprTextPrinter::class)]
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
final class ClassLikeBuilderTest extends TestCase
{
    public function testBuildBuildsNamedClassModel(): void
    {
        $code = <<<'PHP'
<?php

abstract class Sample implements Countable
{
    public const LIMIT = 5;

    private string $name = 'x';

    public function count(): int
    {
        return 1;
    }
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);
        $context = new SymbolContext('', ['countable' => 'Countable'], 'demo/pkg', 'src/Sample.php', false);

        $doc = (new ClassLikeBuilder())->build($statement, $context);

        self::assertNotNull($doc);
        self::assertSame('Sample', $doc->fqcn);
        self::assertSame('Sample', $doc->shortName);
        self::assertSame('', $doc->namespace);
        self::assertSame('class', $doc->kind);
        self::assertSame('demo/pkg', $doc->packageName);
        self::assertSame('src/Sample.php', $doc->file);
        self::assertSame(3, $doc->startLine);
        self::assertSame(13, $doc->endLine);
        self::assertTrue($doc->isAbstract);
        self::assertFalse($doc->isFinal);
        self::assertSame([], $doc->extends);
        self::assertSame(['Countable'], $doc->implements);
        self::assertSame([], $doc->traits);
        self::assertCount(1, $doc->constants);
        self::assertCount(1, $doc->properties);
        self::assertCount(1, $doc->methods);
        self::assertSame([], $doc->enumCases);
        self::assertNull($doc->backingType);
        self::assertNull($doc->docBlock);
        self::assertSame(['countable' => 'Countable'], $doc->useMap);
        self::assertFalse($doc->isDev);
    }

    public function testBuildReturnsNullForAnonymousClass(): void
    {
        $statement = (new AstParser())->parse('<?php new class {};', 'anon.php')[0];
        self::assertInstanceOf(Expression::class, $statement);
        $new = $statement->expr;
        self::assertInstanceOf(New_::class, $new);
        $class = $new->class;
        self::assertInstanceOf(Class_::class, $class);

        self::assertNull((new ClassLikeBuilder())->build($class, new SymbolContext('', [], 'demo/pkg', 'anon.php', false)));
    }

    public function testKindOfDistinguishesClassLikeKinds(): void
    {
        $class = (new AstParser())->parse('<?php class A {}', 'a.php')[0];
        self::assertInstanceOf(Class_::class, $class);
        $interface = (new AstParser())->parse('<?php interface B {}', 'b.php')[0];
        self::assertInstanceOf(Interface_::class, $interface);
        $trait = (new AstParser())->parse('<?php trait C {}', 'c.php')[0];
        self::assertInstanceOf(Trait_::class, $trait);
        $enum = (new AstParser())->parse('<?php enum D {}', 'd.php')[0];
        self::assertInstanceOf(Enum_::class, $enum);

        self::assertSame('class', (new ClassLikeBuilder())->kindOf($class));
        self::assertSame('interface', (new ClassLikeBuilder())->kindOf($interface));
        self::assertSame('trait', (new ClassLikeBuilder())->kindOf($trait));
        self::assertSame('enum', (new ClassLikeBuilder())->kindOf($enum));
    }

    public function testParentsReadsClassExtendsAndImplements(): void
    {
        $statement = (new AstParser())->parse('<?php class Sample extends Base implements Countable, Stringable {}', 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);

        self::assertSame(
            ['extends' => ['Base'], 'implements' => ['Countable', 'Stringable'], 'backing' => null],
            (new ClassLikeBuilder())->parents($statement),
        );
    }

    public function testParentsReadsInterfaceExtends(): void
    {
        $statement = (new AstParser())->parse('<?php interface Combined extends Countable, Stringable {}', 'combined.php')[0];
        self::assertInstanceOf(Interface_::class, $statement);

        self::assertSame(
            ['extends' => ['Countable', 'Stringable'], 'implements' => [], 'backing' => null],
            (new ClassLikeBuilder())->parents($statement),
        );
    }

    public function testParentsReadsEnumBackingTypeAndInterfaces(): void
    {
        $statement = (new AstParser())->parse('<?php enum Suit: string implements Stringable {}', 'suit.php')[0];
        self::assertInstanceOf(Enum_::class, $statement);

        self::assertSame(
            ['extends' => [], 'implements' => ['Stringable'], 'backing' => 'string'],
            (new ClassLikeBuilder())->parents($statement),
        );
    }

    public function testTraitNamesCollectsUsedTraits(): void
    {
        $statement = (new AstParser())->parse('<?php class Sample { use FirstTrait; use SecondTrait; }', 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);
        $bare = (new AstParser())->parse('<?php class Bare {}', 'bare.php')[0];
        self::assertInstanceOf(Class_::class, $bare);

        self::assertSame(['FirstTrait', 'SecondTrait'], (new ClassLikeBuilder())->traitNames($statement));
        self::assertSame([], (new ClassLikeBuilder())->traitNames($bare));
    }

    public function testMembersBuildsConstantPropertyAndMethodModels(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    public const LIMIT = 5;

    private bool $done = false;

    public function run(): void
    {
    }
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);

        $members = (new ClassLikeBuilder())->members($statement);

        self::assertCount(1, $members['constants']);
        self::assertSame('LIMIT', $members['constants'][0]->name);
        self::assertCount(1, $members['properties']);
        self::assertSame('done', $members['properties'][0]->name);
        self::assertCount(1, $members['methods']);
        self::assertSame('run', $members['methods'][0]->name);
        self::assertSame([], $members['cases']);
    }

    public function testMembersCollectsEnumCases(): void
    {
        $code = <<<'PHP'
<?php

enum Suit: string
{
    case Hearts = 'h';
    case Spades = 's';
}
PHP;
        $statement = (new AstParser())->parse($code, 'suit.php')[0];
        self::assertInstanceOf(Enum_::class, $statement);

        $members = (new ClassLikeBuilder())->members($statement);

        self::assertCount(2, $members['cases']);
        self::assertSame('Hearts', $members['cases'][0]->name);
        self::assertSame('Spades', $members['cases'][1]->name);
    }

    public function testMembersAppendsPromotedConstructorProperties(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    private string $label = 'x';

    public function __construct(protected int $count = 0)
    {
    }
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);

        $members = (new ClassLikeBuilder())->members($statement);

        self::assertCount(2, $members['properties']);
        self::assertSame('label', $members['properties'][0]->name);
        self::assertSame('count', $members['properties'][1]->name);
        self::assertTrue($members['properties'][1]->isPromoted);
        self::assertSame('protected', $members['properties'][1]->visibility);
        self::assertSame('0', $members['properties'][1]->defaultText);
        self::assertSame('int', $members['properties'][1]->type->native);
    }

    public function testPromotedPropertiesBuildsModelsForPromotedParametersOnly(): void
    {
        $code = <<<'PHP'
<?php

final class Sample
{
    public function __construct(private int $count, string $label)
    {
    }
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);
        $constructor = $statement->getMethod('__construct');
        self::assertNotNull($constructor);
        $method = (new MethodBuilder())->build($constructor);

        $properties = (new ClassLikeBuilder())->promotedProperties($method->parameters, $constructor);

        self::assertCount(1, $properties);
        self::assertSame('count', $properties[0]->name);
        self::assertSame('private', $properties[0]->visibility);
        self::assertFalse($properties[0]->isStatic);
        self::assertTrue($properties[0]->isPromoted);
        self::assertSame('int', $properties[0]->type->native);
        self::assertNull($properties[0]->defaultText);
        self::assertNull($properties[0]->docBlock);
        self::assertSame(5, $properties[0]->line);
    }
}
