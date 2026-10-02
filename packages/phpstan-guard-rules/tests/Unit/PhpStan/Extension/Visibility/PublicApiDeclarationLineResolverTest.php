<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Extension\Visibility;

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
        $file = __DIR__ . '/../../../../Fixture/VisibilityPublicUnused/Declarations.php';

        self::assertSame([12, 19, 26, 33], (new PublicApiDeclarationLineResolver())->lines($file));
    }

    public function testDeclaresPublicAtRejectsAnInternalDeclaration(): void
    {
        $file = __DIR__ . '/../../../../Fixture/VisibilityPublicUnused/Declarations.php';

        self::assertFalse((new PublicApiDeclarationLineResolver())->declaresPublicAt($file, 40));
    }

    public function testWalkReturnsNestedDeclarationNodes(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Order', ['stmts' => [new \PhpParser\Node\Stmt\ClassMethod('run')]]);

        self::assertCount(4, (new PublicApiDeclarationLineResolver())->walk([$class]));
    }

    public function testIsDeclarationAcceptsClassMembers(): void
    {
        self::assertTrue((new PublicApiDeclarationLineResolver())->isDeclaration(new \PhpParser\Node\Stmt\ClassMethod('run')));
    }

    public function testDeclarationLinesIncludesEveryConstantInAStatement(): void
    {
        $constant = new \PhpParser\Node\Stmt\ClassConst([
            new \PhpParser\Node\Const_('ONE', new \PhpParser\Node\Scalar\LNumber(1)),
            new \PhpParser\Node\Const_('TWO', new \PhpParser\Node\Scalar\LNumber(2)),
        ]);

        self::assertSame([-1, -1, -1], (new PublicApiDeclarationLineResolver())->declarationLines($constant));
    }
}
