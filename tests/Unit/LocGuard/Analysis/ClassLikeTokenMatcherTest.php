<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\ClassLikeTokenMatcher;
use PhpAiToolkit\LocGuard\Analysis\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use const T_CLASS;
use const T_FUNCTION;
use const T_STRING;
use const T_WHITESPACE;

#[CoversClass(ClassLikeTokenMatcher::class)]
#[UsesClass(PhpTokenNavigator::class)]
final class ClassLikeTokenMatcherTest extends TestCase
{
    public function testIsClassLikeTokenReturnsTrueForClassToken(): void
    {
        $tokens = [
            new PhpToken(T_CLASS, 'class', 1, 0),
        ];

        self::assertTrue((new ClassLikeTokenMatcher())->isClassLikeToken($tokens, 0));
    }

    public function testIsClassLikeTokenReturnsTrueForEnumTextToken(): void
    {
        $tokens = [
            new PhpToken(T_STRING, 'enum', 1, 0),
            new PhpToken(T_WHITESPACE, ' ', 1, 4),
            new PhpToken(T_STRING, 'Status', 1, 5),
        ];

        self::assertTrue((new ClassLikeTokenMatcher())->isClassLikeToken($tokens, 0));
    }

    public function testIsClassLikeTokenReturnsFalseForFunctionNamedEnum(): void
    {
        $tokens = [
            new PhpToken(T_FUNCTION, 'function', 1, 0),
            new PhpToken(T_WHITESPACE, ' ', 1, 8),
            new PhpToken(T_STRING, 'enum', 1, 9),
            new PhpToken(40, '(', 1, 13),
        ];

        self::assertFalse((new ClassLikeTokenMatcher())->isClassLikeToken($tokens, 2));
    }

    public function testIsEnumTokenReturnsTrueForEnumTextToken(): void
    {
        self::assertTrue((new ClassLikeTokenMatcher())->isEnumToken(new PhpToken(T_STRING, 'enum', 1, 0)));
    }
}
