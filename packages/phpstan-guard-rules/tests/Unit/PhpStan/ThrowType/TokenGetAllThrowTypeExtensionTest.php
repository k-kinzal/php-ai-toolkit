<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\ThrowType;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\ThrowType\TokenGetAllThrowTypeExtension;

/**
 * @covers \Toolkit\PhpStan\ThrowType\TokenGetAllThrowTypeExtension
 */
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
        $call = new FuncCall(
            new Name('token_get_all'),
            [new Arg(new Variable('code'))],
        );

        self::assertNull((new TokenGetAllThrowTypeExtension())->getThrowTypeFromFunctionCall(
            self::createStub(FunctionReflection::class),
            $call,
            self::createStub(Scope::class),
        ));
    }

    public function testGetThrowTypeFromFunctionCallReturnsNullWhenTokenParseFlagIsAbsent(): void
    {
        $call = new FuncCall(
            new Name('token_get_all'),
            [
                new Arg(new Variable('code')),
                new Arg(new Variable('flags')),
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
        $call = new FuncCall(
            new Name('token_get_all'),
            [
                new Arg(new Variable('code')),
                new Arg(new Variable('flags')),
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
        $call = new FuncCall(
            new Name('token_get_all'),
            [
                new Arg(new Variable('code')),
                new Arg(new Variable('flags')),
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
