<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Extension\Visibility;

use PhpParser\Node\Const_;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Extension\Visibility\ParserFactoryBridge;
use Toolkit\PhpStan\Extension\Visibility\PublicApiDeclarationLineResolver;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector;

/**
 * @covers \Toolkit\PhpStan\Extension\Visibility\PublicApiDeclarationLineResolver
 * @uses \Toolkit\PhpStan\Extension\Visibility\ParserFactoryBridge
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector
 */
#[CoversClass(PublicApiDeclarationLineResolver::class)]
#[UsesClass(ParserFactoryBridge::class)]
#[UsesClass(PublicApiVisibilityDetector::class)]
final class PublicApiDeclarationLineResolverTest extends TestCase
{
    public function testLinesContainOnlyExplicitlyPublicDeclarations(): void
    {
        $file = __DIR__ . '/../../../../../tests/PhpStan/VisibilityPublicUnused/Declarations.php';

        self::assertSame([12, 19, 26, 33], (new PublicApiDeclarationLineResolver())->lines($file));
    }

    public function testDeclaresPublicAtRejectsAnInternalDeclaration(): void
    {
        $file = __DIR__ . '/../../../../../tests/PhpStan/VisibilityPublicUnused/Declarations.php';

        self::assertFalse((new PublicApiDeclarationLineResolver())->declaresPublicAt($file, 40));
    }

    public function testWalkReturnsNestedDeclarationNodes(): void
    {
        $class = new Class_('Order', ['stmts' => [new ClassMethod('run')]]);

        self::assertCount(4, (new PublicApiDeclarationLineResolver())->walk([$class]));
    }

    public function testIsDeclarationAcceptsClassMembers(): void
    {
        self::assertTrue((new PublicApiDeclarationLineResolver())->isDeclaration(new ClassMethod('run')));
    }

    public function testDeclarationLinesIncludesEveryConstantInAStatement(): void
    {
        $constant = new ClassConst([
            new Const_('ONE', new LNumber(1)),
            new Const_('TWO', new LNumber(2)),
        ]);

        self::assertSame([-1, -1, -1], (new PublicApiDeclarationLineResolver())->declarationLines($constant));
    }
}
