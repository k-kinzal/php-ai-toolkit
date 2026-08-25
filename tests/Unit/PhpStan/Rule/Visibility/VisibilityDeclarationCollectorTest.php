<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility;

use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\ClassLikeKindLabel;
use Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage;
use Toolkit\PhpStan\Rule\Visibility\VisibilityDeclarationCollector;

/**
 * @covers \Toolkit\PhpStan\Rule\Visibility\VisibilityDeclarationCollector
 * @uses \Toolkit\PhpStan\Rule\Shared\ClassLikeKindLabel
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage
 */
#[CoversClass(VisibilityDeclarationCollector::class)]
#[UsesClass(ClassLikeKindLabel::class)]
#[UsesClass(NamespaceLineage::class)]
final class VisibilityDeclarationCollectorTest extends TestCase
{
    public function testGetNodeTypeReturnsClassLike(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassLike::class, (new VisibilityDeclarationCollector())->getNodeType());
    }

    public function testProcessNodeCollectsClassAndMembers(): void
    {
        $method = new \PhpParser\Node\Stmt\ClassMethod('run');
        $class = new \PhpParser\Node\Stmt\Class_('Order', ['stmts' => [$method]]);
        $class->namespacedName = new \PhpParser\Node\Name('App\Domain\Order');
        $collected = (new VisibilityDeclarationCollector())->processNode($class, self::createStub(Scope::class));

        self::assertSame('App\Domain\Order', $collected['class']['symbol'] ?? null);
        self::assertSame('App\Domain\Order::run()', $collected['members'][0]['symbol'] ?? null);
    }

    public function testMembersCollectPropertiesConstantsAndCases(): void
    {
        $class = new \PhpParser\Node\Stmt\Enum_('Suit', ['stmts' => [
            new \PhpParser\Node\Stmt\Property(1, [new \PhpParser\Node\Stmt\PropertyProperty('label')]),
            new \PhpParser\Node\Stmt\ClassConst([new \PhpParser\Node\Const_('COUNT', new \PhpParser\Node\Scalar\LNumber(1))]),
            new \PhpParser\Node\Stmt\EnumCase('Hearts'),
        ]]);

        self::assertCount(3, (new VisibilityDeclarationCollector())->members($class, 'App\Suit', 'App'));
    }

    public function testMemberFormatsCollectedDeclaration(): void
    {
        $member = (new VisibilityDeclarationCollector())->member('App\Order', 'run', 'App\Order::run()', 'method', 'App', new \PhpParser\Node\Stmt\ClassMethod('run'));

        self::assertSame('run', $member['memberName']);
    }

    public function testSupertypesResolveParentsAndTraits(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\Base', 'App\Feature');
        $class = new \PhpParser\Node\Stmt\Class_('Order', [
            'extends' => new \PhpParser\Node\Name('Base'),
            'stmts' => [new \PhpParser\Node\Stmt\TraitUse([new \PhpParser\Node\Name('Feature')])],
        ]);

        self::assertSame(['App\Base', 'App\Feature'], (new VisibilityDeclarationCollector())->supertypes($class, $scope));
    }

    public function testDocCommentReturnsRawText(): void
    {
        $node = new \PhpParser\Node\Stmt\ClassMethod('run', [], ['comments' => [new \PhpParser\Comment\Doc('/** Summary. */')]]);

        self::assertSame('/** Summary. */', (new VisibilityDeclarationCollector())->docComment($node));
    }
}
