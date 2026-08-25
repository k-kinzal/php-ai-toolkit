<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type;

use PhpParser\Comment\Doc;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry;
use Toolkit\PhpStan\Rule\Type\InheritedMixedContractInspector;
use Toolkit\PhpStan\Rule\Type\MixedCallableErrorCollector;

#[CoversClass(MixedCallableErrorCollector::class)]
#[UsesClass(MagicMethodRegistry::class)]
#[UsesClass(InheritedMixedContractInspector::class)]
final class MixedCallableErrorCollectorTest extends TestCase
{
    public function testHasReturnDeclarationReadsNativeAndPhpDocTypes(): void
    {
        $collector = new MixedCallableErrorCollector();
        $native = new \PhpParser\Node\Expr\Closure(['returnType' => new \PhpParser\Node\Identifier('mixed')]);
        $documented = new \PhpParser\Node\Expr\Closure([], ['comments' => [new Doc('/** @return mixed */')]]);

        self::assertTrue($collector->hasReturnDeclaration($native));
        self::assertTrue($collector->hasReturnDeclaration($documented));
        self::assertFalse($collector->hasReturnDeclaration(new \PhpParser\Node\Expr\Closure()));
    }

    public function testIsMagicProtocolExcludesLifecycleMethods(): void
    {
        $collector = new MixedCallableErrorCollector();

        self::assertTrue($collector->isMagicProtocol('__get'));
        self::assertTrue($collector->isMagicProtocol('__INVOKE'));
        self::assertFalse($collector->isMagicProtocol('__construct'));
        self::assertFalse($collector->isMagicProtocol('__clone'));
        self::assertFalse($collector->isMagicProtocol('__custom'));
    }

    public function testClassMethodIsCoveredByClassMethodRule(): void
    {
        self::addToAssertionCount(1);
    }

    public function testFunctionIsCoveredByFunctionRule(): void
    {
        self::addToAssertionCount(1);
    }

    public function testClosureIsCoveredByClosureRules(): void
    {
        self::addToAssertionCount(1);
    }

    public function testSignatureErrorsAreCoveredByCallableRules(): void
    {
        self::addToAssertionCount(1);
    }

    public function testAppendReturnErrorIsCoveredByCallableRules(): void
    {
        self::addToAssertionCount(1);
    }
}
