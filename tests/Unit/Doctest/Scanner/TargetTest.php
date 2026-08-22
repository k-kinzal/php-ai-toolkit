<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Scanner;

use PhpAiToolkit\Doctest\Scanner\Target;
use PhpAiToolkit\Doctest\Scanner\TargetKind;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Target::class)]
final class TargetTest extends TestCase
{
    public function testGetFullyQualifiedNameSpellsEachKind(): void
    {
        self::assertSame('/app/helpers.php', (new Target(TargetKind::FILE, '/app/helpers.php', '/** */', 'helpers.php', 1))->getFullyQualifiedName());
        self::assertSame('App\Services\Calculator', (new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 5, 'App\Services'))->getFullyQualifiedName());
        self::assertSame('App\Helpers\calculate()', (new Target(TargetKind::FUNCTION, '/a.php', '/** */', 'calculate', 1, 'App\Helpers'))->getFullyQualifiedName());
        self::assertSame('App\Services\Calculator::add()', (new Target(TargetKind::METHOD, '/a.php', '/** */', 'add', 10, 'App\Services', 'Calculator'))->getFullyQualifiedName());
        self::assertSame('Calculator', (new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 5))->getFullyQualifiedName());
    }

    public function testGetShortNameDropsTheNamespace(): void
    {
        self::assertSame('helpers.php', (new Target(TargetKind::FILE, '/app/helpers.php', '/** */', 'helpers.php', 1))->getShortName());
        self::assertSame('Calculator', (new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 5, 'App'))->getShortName());
        self::assertSame('calculate()', (new Target(TargetKind::FUNCTION, '/a.php', '/** */', 'calculate', 1, 'App'))->getShortName());
        self::assertSame('Calculator::add()', (new Target(TargetKind::METHOD, '/a.php', '/** */', 'add', 10, 'App', 'Calculator'))->getShortName());
    }

    public function testExposesTheDocumentedDeclaration(): void
    {
        $target = new Target(TargetKind::METHOD, '/a.php', '/** doc */', 'add', 10, 'App', 'Calculator', true);

        self::assertSame(TargetKind::METHOD, $target->type);
        self::assertSame('/a.php', $target->filePath);
        self::assertSame('/** doc */', $target->docblock);
        self::assertSame('add', $target->name);
        self::assertSame(10, $target->line);
        self::assertSame('App', $target->namespace);
        self::assertSame('Calculator', $target->className);
        self::assertTrue($target->isStatic);
    }

    public function testReadingAPropertyItDoesNotDeclareYieldsNull(): void
    {
        self::assertNull((new Target(TargetKind::FILE, '/a.php', '/** */', 'a.php', 1))->shortName);
    }

    public function testADeclarationIsNotStaticUnlessItIsSaidToBe(): void
    {
        self::assertFalse((new Target(TargetKind::METHOD, '/a.php', '/** */', 'add', 10, 'App', 'Calculator'))->isStatic);
    }
}
