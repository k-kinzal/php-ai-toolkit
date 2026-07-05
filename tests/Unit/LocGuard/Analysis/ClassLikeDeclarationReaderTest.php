<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\ClassLikeDeclarationReader;
use PhpAiToolkit\LocGuard\Analysis\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use const T_CLASS;
use const T_DOUBLE_COLON;
use const T_ENUM;
use const T_EXTENDS;
use const T_INTERFACE;
use const T_STRING;
use const T_TRAIT;
use const T_WHITESPACE;

#[CoversClass(ClassLikeDeclarationReader::class)]
#[UsesClass(PhpTokenNavigator::class)]
final class ClassLikeDeclarationReaderTest extends TestCase
{
    public function testIsDeclarationReturnsTrueForClassLikeToken(): void
    {
        $tokens = [
            new PhpToken(T_CLASS, 'class', 1, 0),
        ];

        self::assertTrue((new ClassLikeDeclarationReader())->isDeclaration($tokens, 0));
    }

    public function testIsDeclarationReturnsFalseForStaticClassConstant(): void
    {
        $tokens = [
            new PhpToken(T_STRING, 'Example', 1, 0),
            new PhpToken(T_DOUBLE_COLON, '::', 1, 7),
            new PhpToken(T_CLASS, 'class', 1, 9),
        ];

        self::assertFalse((new ClassLikeDeclarationReader())->isDeclaration($tokens, 2));
    }

    public function testKindReturnsClassLikeKind(): void
    {
        $reader = new ClassLikeDeclarationReader();

        self::assertSame('class', $reader->kind(new PhpToken(T_CLASS, 'class', 1, 0)));
        self::assertSame('interface', $reader->kind(new PhpToken(T_INTERFACE, 'interface', 1, 0)));
        self::assertSame('trait', $reader->kind(new PhpToken(T_TRAIT, 'trait', 1, 0)));
        self::assertSame('enum', $reader->kind(new PhpToken(T_ENUM, 'enum', 1, 0)));
    }

    public function testNameReturnsNamedClassName(): void
    {
        $tokens = [
            new PhpToken(T_CLASS, 'class', 1, 0),
            new PhpToken(T_WHITESPACE, ' ', 1, 5),
            new PhpToken(T_STRING, 'Example', 1, 6),
            new PhpToken(123, '{', 1, 14),
        ];

        self::assertSame('Example', (new ClassLikeDeclarationReader())->name($tokens, 0));
    }

    public function testNameReturnsAnonymousLineWhenArgumentsFollowClass(): void
    {
        $tokens = [
            new PhpToken(T_CLASS, 'class', 12, 0),
            new PhpToken(T_WHITESPACE, ' ', 12, 5),
            new PhpToken(40, '(', 12, 6),
        ];

        self::assertSame('anonymous@12', (new ClassLikeDeclarationReader())->name($tokens, 0));
    }

    public function testNameReturnsAnonymousLineWhenExtendsAppearsBeforeName(): void
    {
        $tokens = [
            new PhpToken(T_CLASS, 'class', 7, 0),
            new PhpToken(T_WHITESPACE, ' ', 7, 5),
            new PhpToken(T_EXTENDS, 'extends', 7, 6),
        ];

        self::assertSame('anonymous@7', (new ClassLikeDeclarationReader())->name($tokens, 0));
    }
}
