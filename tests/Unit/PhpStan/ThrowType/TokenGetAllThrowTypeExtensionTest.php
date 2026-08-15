<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\ThrowType;

use PhpAiToolkit\PhpStan\ThrowType\TokenGetAllThrowTypeExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TokenGetAllThrowTypeExtension::class)]
final class TokenGetAllThrowTypeExtensionTest extends TestCase
{
    public function testIsFunctionSupportedAcceptsTokenGetAll(): void
    {
        $function = self::createStub(FunctionReflection::class);
        $function->method('getName')->willReturn('token_get_all');

        self::assertTrue((new TokenGetAllThrowTypeExtension())->isFunctionSupported($function));
    }

    public function testIsFunctionSupportedRejectsOtherFunctions(): void
    {
        $function = self::createStub(FunctionReflection::class);
        $function->method('getName')->willReturn('token_name');

        self::assertFalse((new TokenGetAllThrowTypeExtension())->isFunctionSupported($function));
    }

    public function testGetThrowTypeFromFunctionCallReturnsNullWithoutFlagsArgument(): void
    {
        $call = new \PhpParser\Node\Expr\FuncCall(
            new \PhpParser\Node\Name('token_get_all'),
            [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('code'))],
        );

        self::assertNull((new TokenGetAllThrowTypeExtension())->getThrowTypeFromFunctionCall(
            self::createStub(FunctionReflection::class),
            $call,
            self::createStub(Scope::class),
        ));
    }

    public function testGetThrowTypeFromFunctionCallReturnsNullWhenTokenParseFlagIsAbsent(): void
    {
        $call = new \PhpParser\Node\Expr\FuncCall(
            new \PhpParser\Node\Name('token_get_all'),
            [
                new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('code')),
                new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('flags')),
            ],
        );
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantIntegerType(0));

        self::assertNull((new TokenGetAllThrowTypeExtension())->getThrowTypeFromFunctionCall(
            self::createStub(FunctionReflection::class),
            $call,
            $scope,
        ));
    }

    public function testGetThrowTypeFromFunctionCallReturnsParseErrorWhenTokenParseFlagIsSet(): void
    {
        $call = new \PhpParser\Node\Expr\FuncCall(
            new \PhpParser\Node\Name('token_get_all'),
            [
                new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('code')),
                new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('flags')),
            ],
        );
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantIntegerType(TOKEN_PARSE));

        $throwType = (new TokenGetAllThrowTypeExtension())->getThrowTypeFromFunctionCall(
            self::createStub(FunctionReflection::class),
            $call,
            $scope,
        );

        self::assertInstanceOf(ObjectType::class, $throwType);
        self::assertSame('ParseError', $throwType->getClassName());
    }

    public function testGetThrowTypeFromFunctionCallReturnsParseErrorForUnknownFlagValue(): void
    {
        $call = new \PhpParser\Node\Expr\FuncCall(
            new \PhpParser\Node\Name('token_get_all'),
            [
                new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('code')),
                new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('flags')),
            ],
        );
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new IntegerType());

        $throwType = (new TokenGetAllThrowTypeExtension())->getThrowTypeFromFunctionCall(
            self::createStub(FunctionReflection::class),
            $call,
            $scope,
        );

        self::assertInstanceOf(ObjectType::class, $throwType);
        self::assertSame('ParseError', $throwType->getClassName());
    }
}
