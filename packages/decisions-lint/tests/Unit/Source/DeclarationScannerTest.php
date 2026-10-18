<?php

declare(strict_types=1);

namespace Tests\Unit\Source;

use DecisionsLint\Source\Declaration;
use DecisionsLint\Source\DeclarationScanner;
use DecisionsLint\Source\Tokens;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Source\DeclarationScanner
 * @uses \DecisionsLint\Source\Declaration
 * @uses \DecisionsLint\Source\Tokens
 */
#[CoversClass(DeclarationScanner::class)]
#[UsesClass(Declaration::class)]
#[UsesClass(Tokens::class)]
final class DeclarationScannerTest extends TestCase
{
    public function testScanFindsClassLikesMembersAndFunctionsButSkipsBodies(): void
    {
        $code = <<<'PHP'
            <?php
            namespace App\Analysis;

            /**
             * Doc.
             */
            final class Analyzer extends Base
            {
                public const X = 1;
                /** @var list<string> */
                private array $items = [];
                public static ?int $count = null;
                use SomeTrait;

                public function __construct(private string $name) {}

                public function analyze(string $input): Result
                {
                    $f = function (): void { if (true) { echo "{$x}"; } };
                    $anon = new class { public function hidden() {} };
                    return Result::class;
                }

                abstract protected function foo(): void;
            }

            interface Contract { public function analyze(): void; }
            trait Mixin { public function help(): void {} }
            enum Suit: string { case Hearts = 'h'; public function label(): string { return 'x'; } }
            function standalone(int $a): int { return $a; }
            PHP;
        $declarations = (new DeclarationScanner())->scan(new Tokens($code));
        $summary = array_map(static fn (Declaration $declaration): string => $declaration->kind . ' ' . ($declaration->owner === null ? '' : $declaration->owner . '::') . $declaration->name, $declarations);
        self::assertSame([
            'class Analyzer',
            'property Analyzer::items',
            'property Analyzer::count',
            'method Analyzer::__construct',
            'method Analyzer::analyze',
            'method Analyzer::foo',
            'interface Contract',
            'method Contract::analyze',
            'trait Mixin',
            'method Mixin::help',
            'enum Suit',
            'method Suit::label',
            'function standalone',
        ], $summary);
        self::assertSame('App\Analysis', $declarations[0]->namespace);
        self::assertSame(['final'], $declarations[0]->modifiers);
        self::assertSame(['abstract', 'protected'], $declarations[5]->modifiers);
    }

    public function testNamespaceNameReadsQualifiedNamesAndTheGlobalBlock(): void
    {
        $scanner = new DeclarationScanner();
        self::assertSame('App\Deep', $scanner->namespaceName(new Tokens("<?php\nnamespace App\\Deep;"), 1));
        self::assertSame('', $scanner->namespaceName(new Tokens("<?php\nnamespace { }"), 1));
    }

    public function testIsClassLikeKeywordIgnoresClassConstantsAndAnonymousClasses(): void
    {
        $scanner = new DeclarationScanner();
        $named = new Tokens("<?php\nclass A {}");
        self::assertTrue($scanner->isClassLikeKeyword($named, 1));
        $constant = new Tokens("<?php\n\$x = Foo::class;");
        self::assertFalse($scanner->isClassLikeKeyword($constant, (int) $constant->find(0, ['class'])));
        $anonymous = new Tokens("<?php\n\$x = new class {};");
        self::assertFalse($scanner->isClassLikeKeyword($anonymous, (int) $anonymous->find(0, ['class'])));
        $call = new Tokens("<?php\nenum(1);");
        self::assertFalse($scanner->isClassLikeKeyword($call, 1));
    }

    public function testIsEnumKeywordAcceptsTheWordHoweverItIsTokenized(): void
    {
        $scanner = new DeclarationScanner();
        $tokens = new Tokens("<?php\nenum Suit {}");
        self::assertTrue($scanner->isEnumKeyword($tokens->at(1)));
        self::assertFalse($scanner->isEnumKeyword($tokens->at(0)));
    }

    public function testFollowsClassNameAcceptsBraceExtendsImplementsAndBackingType(): void
    {
        $scanner = new DeclarationScanner();
        self::assertTrue($scanner->followsClassName(new Tokens("<?php\nclass A {}"), 3));
        self::assertTrue($scanner->followsClassName(new Tokens("<?php\nclass A extends B {}"), 3));
        self::assertTrue($scanner->followsClassName(new Tokens("<?php\nenum A: string {}"), 3));
        self::assertFalse($scanner->followsClassName(new Tokens("<?php\nclass A"), 3));
        self::assertFalse($scanner->followsClassName(new Tokens("<?php\nclass A = 1;"), 3));
    }

    public function testIsNamedFunctionDistinguishesClosures(): void
    {
        $scanner = new DeclarationScanner();
        self::assertTrue($scanner->isNamedFunction(new Tokens("<?php\nfunction f() {}"), 1));
        self::assertTrue($scanner->isNamedFunction(new Tokens("<?php\nfunction &f() {}"), 1));
        self::assertFalse($scanner->isNamedFunction(new Tokens("<?php\nfunction () {};"), 1));
    }

    public function testStartsPropertyRecognisesOnlyPropertyStatements(): void
    {
        $scanner = new DeclarationScanner();
        $property = new Tokens("<?php\nclass A { public static ?int \$x = null; }");
        self::assertTrue($scanner->startsProperty($property, (int) $property->find(0, ['public'])));
        self::assertFalse($scanner->startsProperty($property, (int) $property->find(0, ['static'])));
        $method = new Tokens("<?php\nclass A { public function f(\$x) {} }");
        self::assertFalse($scanner->startsProperty($method, (int) $method->find(0, ['public'])));
        $constant = new Tokens("<?php\nclass A { public const X = 1; }");
        self::assertFalse($scanner->startsProperty($constant, (int) $constant->find(0, ['public'])));
        $unterminated = new Tokens("<?php\nclass A { public \$x");
        self::assertFalse($scanner->startsProperty($unterminated, (int) $unterminated->find(0, ['public'])));
        $other = new Tokens("<?php\nclass A { use T; }");
        self::assertFalse($scanner->startsProperty($other, (int) $other->find(0, ['use'])));
    }

    public function testClassLikeDescribesTheDeclarationRange(): void
    {
        $tokens = new Tokens("<?php\n/** doc */\nabstract class A extends B\n{\n}\n");
        $declaration = (new DeclarationScanner())->classLike($tokens, (int) $tokens->find(0, ['class']), 'App', null);
        self::assertSame('class', $declaration->kind);
        self::assertSame('A', $declaration->name);
        self::assertSame(['abstract'], $declaration->modifiers);
        self::assertSame('/** doc */', $tokens->at($declaration->docStart)->text);
        self::assertSame('abstract', $tokens->at($declaration->start)->text);
        self::assertSame('B', $tokens->at($declaration->nameEnd)->text);
        self::assertSame('}', $tokens->at($declaration->end)->text);
    }

    public function testFunctionDescribesMethodsAndBodilessDeclarations(): void
    {
        $tokens = new Tokens("<?php\nclass A { public static function &f(int \$a = 1): ?B { return null; } abstract function g(); }");
        $scanner = new DeclarationScanner();
        $method = $scanner->function($tokens, (int) $tokens->find(0, ['function']), '', 'A');
        self::assertSame('method', $method->kind);
        self::assertSame('f', $method->name);
        self::assertSame(['public', 'static'], $method->modifiers);
        self::assertSame('}', $tokens->at($method->end)->text);
        self::assertSame('B', $tokens->at($method->nameEnd)->text);
        $abstract = $scanner->function($tokens, (int) $tokens->find($method->end, ['function']), '', null);
        self::assertSame('function', $abstract->kind);
        self::assertSame(';', $tokens->at($abstract->end)->text);
    }

    public function testPropertyNamesTheVariableAndEndsAtTheSemicolon(): void
    {
        $tokens = new Tokens("<?php\nclass A {\n    /** @var int */\n    protected static int \$count = 0;\n}");
        $declaration = (new DeclarationScanner())->property($tokens, (int) $tokens->find(0, ['protected']), 'App', 'A');
        self::assertSame('property', $declaration->kind);
        self::assertSame('count', $declaration->name);
        self::assertSame('A', $declaration->owner);
        self::assertSame(['protected', 'static'], $declaration->modifiers);
        self::assertSame(';', $tokens->at($declaration->end)->text);
        self::assertSame('/** @var int */', $tokens->at($declaration->docStart)->text);
    }

    public function testDeclarationStartWalksBackOverModifiers(): void
    {
        $tokens = new Tokens("<?php\nfinal public static function f() {}");
        $function = (int) $tokens->find(0, ['function']);
        self::assertSame('final', $tokens->at((new DeclarationScanner())->declarationStart($tokens, $function))->text);
        self::assertSame(1, (new DeclarationScanner())->declarationStart(new Tokens("<?php\nfunction f() {}"), 1));
    }

    public function testDocStartReturnsTheDocblockOrTheStart(): void
    {
        $scanner = new DeclarationScanner();
        $documented = new Tokens("<?php\n/** doc */\nfunction f() {}");
        $function = (int) $documented->find(0, ['function']);
        self::assertSame('/** doc */', $documented->at($scanner->docStart($documented, $function))->text);
        $separated = new Tokens("<?php\n/** doc */\n\$x = 1;\nfunction f() {}");
        $function = (int) $separated->find(0, ['function']);
        self::assertSame($function, $scanner->docStart($separated, $function));
        self::assertSame(0, $scanner->docStart(new Tokens('<?php'), 0));
    }

    public function testModifiersCollectsTheLowerCaseKeywordsInARange(): void
    {
        $tokens = new Tokens("<?php\nPublic STATIC readonly function f() {}");
        self::assertSame(['public', 'static', 'readonly'], (new DeclarationScanner())->modifiers($tokens, 1, (int) $tokens->find(0, ['function'])));
    }
}
