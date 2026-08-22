<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PhpAiToolkit\Doctest\Parser\Example;
use PhpAiToolkit\Doctest\Parser\ExampleExtractor;
use PhpAiToolkit\Doctest\Scanner\Target;
use PhpAiToolkit\Doctest\Scanner\TargetKind;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\MissingExampleErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApiExampleErrorCollector;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApiVisibilityDetector;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\RunnableExampleDetector;
use PhpAiToolkit\PhpStan\Rule\Shared\LineOrderedErrors;
use PhpParser\Comment\Doc;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PublicApiExampleErrorCollector::class)]
#[UsesClass(PublicApiVisibilityDetector::class)]
#[UsesClass(RunnableExampleDetector::class)]
#[UsesClass(MissingExampleErrorBuilder::class)]
#[UsesClass(LineOrderedErrors::class)]
#[UsesClass(Example::class)]
#[UsesClass(ExampleExtractor::class)]
#[UsesClass(Target::class)]
#[UsesClass(TargetKind::class)]
final class PublicApiExampleErrorCollectorTest extends TestCase
{
    public function testErrorsCollectsClassAndMemberErrorsInLineOrder(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Ledger', [
            'stmts' => [
                new \PhpParser\Node\Stmt\ClassMethod('append', [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")], 'startLine' => 30]),
            ],
        ], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")], 'startLine' => 10]);

        $errors = (new PublicApiExampleErrorCollector())->errors($class, 'class', 'Ledger');

        self::assertCount(2, $errors);
        self::assertSame('customRules.requireExampleOnClass', $errors[0]->getIdentifier());
        self::assertSame('customRules.requireExampleOnMethod', $errors[1]->getIdentifier());
    }

    public function testClassErrorsNamesTheDeclarationKind(): void
    {
        $interface = new \PhpParser\Node\Stmt\Interface_('Writer', [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")], 'startLine' => 8]);

        $errors = (new PublicApiExampleErrorCollector())->classErrors($interface, 'interface', 'Writer');

        self::assertStringContainsString('interface Writer', $errors[0]->getMessage());
    }

    public function testMethodErrorsReportsEveryUndocumentedPublicApiMethod(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Ledger', [
            'stmts' => [
                new \PhpParser\Node\Stmt\ClassMethod('append', [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")]]),
                new \PhpParser\Node\Stmt\ClassMethod('close', [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n *\n * @example Using it\n * run() // => 1\n */")]]),
            ],
        ]);

        $errors = (new PublicApiExampleErrorCollector())->methodErrors($class, 'Ledger');

        self::assertCount(1, $errors);
        self::assertStringContainsString('method Ledger::append()', $errors[0]->getMessage());
    }

    public function testPropertyErrorsNamesTheDeclaredProperty(): void
    {
        $property = new \PhpParser\Node\Stmt\Property(
            0,
            [new \PhpParser\Node\Stmt\PropertyProperty('name')],
            ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")]],
        );
        $class = new \PhpParser\Node\Stmt\Class_('Ledger', ['stmts' => [$property]]);

        $errors = (new PublicApiExampleErrorCollector())->propertyErrors($class, 'Ledger');

        self::assertStringContainsString('property Ledger::$name', $errors[0]->getMessage());
    }

    public function testConstantErrorsNamesTheDeclaredConstant(): void
    {
        $constant = new \PhpParser\Node\Stmt\ClassConst(
            [new \PhpParser\Node\Const_('VERSION', new \PhpParser\Node\Scalar\String_('1.0'))],
            0,
            ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")]],
        );
        $class = new \PhpParser\Node\Stmt\Class_('Ledger', ['stmts' => [$constant]]);

        $errors = (new PublicApiExampleErrorCollector())->constantErrors($class, 'Ledger');

        self::assertStringContainsString('constant Ledger::VERSION', $errors[0]->getMessage());
    }

    public function testEnumCaseErrorsNamesTheDeclaredCase(): void
    {
        $case = new \PhpParser\Node\Stmt\EnumCase('Hearts', null, [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")]]);
        $enum = new \PhpParser\Node\Stmt\Enum_('Suit', ['stmts' => [$case]]);

        $errors = (new PublicApiExampleErrorCollector())->enumCaseErrors($enum, 'Suit');

        self::assertStringContainsString('enum case Suit::Hearts', $errors[0]->getMessage());
    }

    public function testErrorsForSkipsDocumentedAndUntaggedDeclarations(): void
    {
        $collector = new PublicApiExampleErrorCollector();
        $documented = new \PhpParser\Node\Stmt\Class_('Ledger', [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n *\n * @example Using it\n * run() // => 1\n */")]]);
        $untagged = new \PhpParser\Node\Stmt\Class_('Ledger', [], ['comments' => [new Doc('/** Summary. */')]]);
        $undocumented = new \PhpParser\Node\Stmt\Class_('Ledger', [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")]]);

        self::assertSame([], $collector->errorsFor($documented, 'customRules.requireExampleOnClass', 'class Ledger'));
        self::assertSame([], $collector->errorsFor($untagged, 'customRules.requireExampleOnClass', 'class Ledger'));
        self::assertSame([], $collector->errorsFor(new \PhpParser\Node\Stmt\Class_('Ledger'), 'customRules.requireExampleOnClass', 'class Ledger'));
        self::assertCount(1, $collector->errorsFor($undocumented, 'customRules.requireExampleOnClass', 'class Ledger'));
    }
}
