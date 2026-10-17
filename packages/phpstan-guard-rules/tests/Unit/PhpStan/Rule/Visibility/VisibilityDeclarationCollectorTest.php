<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility;

use PhpParser\Comment\Doc;
use PhpParser\Node\Const_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\TraitUse;
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
        self::assertSame(ClassLike::class, (new VisibilityDeclarationCollector())->getNodeType());
    }

    public function testProcessNodeCollectsClassAndMembers(): void
    {
        $method = new ClassMethod('run');
        $class = new Class_('Order', ['stmts' => [$method]]);
        $class->namespacedName = new Name('App\Domain\Order');
        $collected = (new VisibilityDeclarationCollector())->processNode($class, self::createStub(Scope::class));

        self::assertSame('App\Domain\Order', $collected['class']['symbol'] ?? null);
        self::assertSame('App\Domain\Order::run()', $collected['members'][0]['symbol'] ?? null);
    }

    public function testMembersCollectPropertiesConstantsAndCases(): void
    {
        $class = new Enum_('Suit', ['stmts' => [
            new Property(1, [new PropertyProperty('label')]),
            new ClassConst([new Const_('COUNT', new LNumber(1))]),
            new EnumCase('Hearts'),
        ]]);

        self::assertCount(3, (new VisibilityDeclarationCollector())->members($class, 'App\Suit', 'App'));
    }

    public function testMemberFormatsCollectedDeclaration(): void
    {
        $member = (new VisibilityDeclarationCollector())->member('App\Order', 'run', 'App\Order::run()', 'method', 'App', new ClassMethod('run'));

        self::assertSame('run', $member['memberName']);
    }

    public function testSupertypesResolveParentsAndTraits(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\Base', 'App\Feature');
        $class = new Class_('Order', [
            'extends' => new Name('Base'),
            'stmts' => [new TraitUse([new Name('Feature')])],
        ]);

        self::assertSame(['App\Base', 'App\Feature'], (new VisibilityDeclarationCollector())->supertypes($class, $scope));
    }

    public function testDocCommentReturnsRawText(): void
    {
        $node = new ClassMethod('run', [], ['comments' => [new Doc('/** Summary. */')]]);

        self::assertSame('/** Summary. */', (new VisibilityDeclarationCollector())->docComment($node));
    }
}
