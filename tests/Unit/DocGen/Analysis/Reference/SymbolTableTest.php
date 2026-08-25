<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Reference;

use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\TypeSignature
 */
#[CoversClass(SymbolTable::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(TypeSignature::class)]
final class SymbolTableTest extends TestCase
{
    public function testRegisterClassLikeKeepsFirstRegistrationForDuplicateName(): void
    {
        $table = new SymbolTable();
        $table->registerClassLike(new ClassLikeDoc('Demo\Greeter', 'Greeter', 'Demo', 'class', 'demo/app', 'src/Greeter.php', 1, 5, false, false, [], [], [], [], [], [], [], null, null, [], false));
        $table->registerClassLike(new ClassLikeDoc('demo\greeter', 'greeter', 'demo', 'class', 'demo/app', 'src/Duplicate.php', 1, 5, false, false, [], [], [], [], [], [], [], null, null, [], false));

        $found = $table->classLike('Demo\Greeter');

        self::assertNotNull($found);
        self::assertSame('src/Greeter.php', $found->file);
    }

    public function testClassLikeIgnoresCaseAndLeadingBackslash(): void
    {
        $table = new SymbolTable();
        $table->registerClassLike(new ClassLikeDoc('Demo\Greeter', 'Greeter', 'Demo', 'class', 'demo/app', 'src/Greeter.php', 1, 5, false, false, [], [], [], [], [], [], [], null, null, [], false));

        $found = $table->classLike('\DEMO\GREETER');

        self::assertNotNull($found);
        self::assertSame('Demo\Greeter', $found->fqcn);
    }

    public function testClassLikeReturnsNullForUnknownName(): void
    {
        self::assertNull((new SymbolTable())->classLike('Demo\Missing'));
    }

    public function testRegisterFunctionKeepsFirstRegistrationForDuplicateName(): void
    {
        $table = new SymbolTable();
        $table->registerFunction(new FunctionDoc('Demo\greet', 'greet', 'Demo', 'demo/app', 'src/functions.php', 3, 6, [], new TypeSignature('string', null), null, [], false));
        $table->registerFunction(new FunctionDoc('demo\GREET', 'GREET', 'demo', 'demo/app', 'src/duplicate.php', 3, 6, [], new TypeSignature('string', null), null, [], false));

        $found = $table->functionNamed('Demo\greet');

        self::assertNotNull($found);
        self::assertSame('src/functions.php', $found->file);
    }

    public function testFunctionNamedIgnoresCaseAndLeadingBackslash(): void
    {
        $table = new SymbolTable();
        $table->registerFunction(new FunctionDoc('Demo\greet', 'greet', 'Demo', 'demo/app', 'src/functions.php', 3, 6, [], new TypeSignature('string', null), null, [], false));

        $found = $table->functionNamed('\DEMO\GREET');

        self::assertNotNull($found);
        self::assertSame('Demo\greet', $found->fqn);
    }

    public function testFunctionNamedReturnsNullForUnknownName(): void
    {
        self::assertNull((new SymbolTable())->functionNamed('Demo\missing'));
    }
}
