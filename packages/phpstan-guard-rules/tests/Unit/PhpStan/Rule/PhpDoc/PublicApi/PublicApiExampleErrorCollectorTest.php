<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc\PublicApi;

use PhpParser\Comment\Doc;
use PhpParser\Node\Const_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\MissingExampleErrorBuilder;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiExampleErrorCollector;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector;
use Toolkit\PhpStan\Rule\PhpDoc\RunnableExampleDetector;
use Toolkit\PhpStan\Rule\Shared\LineOrderedErrors;

/**
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiExampleErrorCollector
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\RunnableExampleDetector
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\MissingExampleErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Shared\LineOrderedErrors
 */
#[CoversClass(PublicApiExampleErrorCollector::class)]
#[UsesClass(PublicApiVisibilityDetector::class)]
#[UsesClass(RunnableExampleDetector::class)]
#[UsesClass(MissingExampleErrorBuilder::class)]
#[UsesClass(LineOrderedErrors::class)]
final class PublicApiExampleErrorCollectorTest extends TestCase
{
    public function testErrorsCollectsClassAndMemberErrorsInLineOrder(): void
    {
        $class = new Class_('Ledger', [
            'stmts' => [
                new ClassMethod('append', [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")], 'startLine' => 30]),
            ],
        ], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")], 'startLine' => 10]);

        $errors = (new PublicApiExampleErrorCollector())->errors($class, 'class', 'Ledger');

        self::assertCount(2, $errors);
        self::assertSame('customRules.requireExampleOnClass', $errors[0]->getIdentifier());
        self::assertSame('customRules.requireExampleOnMethod', $errors[1]->getIdentifier());
    }

    public function testClassErrorsNamesTheDeclarationKind(): void
    {
        $interface = new Interface_('Writer', [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")], 'startLine' => 8]);

        $errors = (new PublicApiExampleErrorCollector())->classErrors($interface, 'interface', 'Writer');

        self::assertStringContainsString('interface Writer', $errors[0]->getMessage());
    }

    public function testMethodErrorsReportsEveryUndocumentedPublicApiMethod(): void
    {
        $class = new Class_('Ledger', [
            'stmts' => [
                new ClassMethod('append', [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")]]),
                new ClassMethod('close', [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n *\n * @example Using it\n * run() // => 1\n */")]]),
            ],
        ]);

        $errors = (new PublicApiExampleErrorCollector())->methodErrors($class, 'Ledger');

        self::assertCount(1, $errors);
        self::assertStringContainsString('method Ledger::append()', $errors[0]->getMessage());
    }

    public function testPropertyErrorsNamesTheDeclaredProperty(): void
    {
        $property = new Property(
            0,
            [new PropertyProperty('name')],
            ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")]],
        );
        $class = new Class_('Ledger', ['stmts' => [$property]]);

        $errors = (new PublicApiExampleErrorCollector())->propertyErrors($class, 'Ledger');

        self::assertStringContainsString('property Ledger::$name', $errors[0]->getMessage());
    }

    public function testConstantErrorsNamesTheDeclaredConstant(): void
    {
        $constant = new ClassConst(
            [new Const_('VERSION', new String_('1.0'))],
            0,
            ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")]],
        );
        $class = new Class_('Ledger', ['stmts' => [$constant]]);

        $errors = (new PublicApiExampleErrorCollector())->constantErrors($class, 'Ledger');

        self::assertStringContainsString('constant Ledger::VERSION', $errors[0]->getMessage());
    }

    public function testEnumCaseErrorsNamesTheDeclaredCase(): void
    {
        $case = new EnumCase('Hearts', null, [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")]]);
        $enum = new Enum_('Suit', ['stmts' => [$case]]);

        $errors = (new PublicApiExampleErrorCollector())->enumCaseErrors($enum, 'Suit');

        self::assertStringContainsString('enum case Suit::Hearts', $errors[0]->getMessage());
    }

    public function testErrorsForSkipsDocumentedAndUntaggedDeclarations(): void
    {
        $collector = new PublicApiExampleErrorCollector();
        $documented = new Class_('Ledger', [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n *\n * @example Using it\n * run() // => 1\n */")]]);
        $untagged = new Class_('Ledger', [], ['comments' => [new Doc('/** Summary. */')]]);
        $undocumented = new Class_('Ledger', [], ['comments' => [new Doc("/**\n * Summary.\n *\n * @visibility public\n */")]]);

        self::assertSame([], $collector->errorsFor($documented, 'customRules.requireExampleOnClass', 'class Ledger'));
        self::assertSame([], $collector->errorsFor($untagged, 'customRules.requireExampleOnClass', 'class Ledger'));
        self::assertSame([], $collector->errorsFor(new Class_('Ledger'), 'customRules.requireExampleOnClass', 'class Ledger'));
        self::assertCount(1, $collector->errorsFor($undocumented, 'customRules.requireExampleOnClass', 'class Ledger'));
    }
}
