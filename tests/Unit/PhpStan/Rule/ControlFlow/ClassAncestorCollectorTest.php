<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Fixture\RequireExhaustiveDispatch\MasterCard;
use Tests\Fixture\RequireExhaustiveDispatch\Payment;
use Toolkit\PhpStan\Rule\ControlFlow\ClassAncestorCollector;

/**
 * @covers \Toolkit\PhpStan\Rule\ControlFlow\ClassAncestorCollector
 * @medium
 */
#[CoversClass(ClassAncestorCollector::class)]
#[Medium]
final class ClassAncestorCollectorTest extends PHPStanTestCase
{
    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(
            \PhpParser\Node\Stmt\ClassLike::class,
            (new ClassAncestorCollector(self::createReflectionProvider()))->getNodeType(),
        );
    }

    public function testProcessNodeNamesAnInstantiableClass(): void
    {
        $node = new \PhpParser\Node\Stmt\Class_('MasterCard');
        $node->namespacedName = new \PhpParser\Node\Name\FullyQualified(MasterCard::class);
        $collected = (new ClassAncestorCollector(self::createReflectionProvider()))->processNode($node, self::createStub(Scope::class));

        self::assertSame(
            [MasterCard::class, true],
            [$collected === null ? null : $collected['name'], $collected === null ? null : $collected['instantiable']],
        );
    }

    public function testProcessNodeRecordsEveryAncestorOfAClass(): void
    {
        $node = new \PhpParser\Node\Stmt\Class_('MasterCard');
        $node->namespacedName = new \PhpParser\Node\Name\FullyQualified(MasterCard::class);
        $collected = (new ClassAncestorCollector(self::createReflectionProvider()))->processNode($node, self::createStub(Scope::class));

        self::assertContains(Payment::class, $collected === null ? [] : $collected['ancestors']);
    }

    public function testProcessNodeMarksAnInterfaceAsNotInstantiable(): void
    {
        $node = new \PhpParser\Node\Stmt\Interface_('Payment');
        $node->namespacedName = new \PhpParser\Node\Name\FullyQualified(Payment::class);
        $collected = (new ClassAncestorCollector(self::createReflectionProvider()))->processNode($node, self::createStub(Scope::class));

        self::assertSame(
            [Payment::class, false],
            [$collected === null ? null : $collected['name'], $collected === null ? null : $collected['instantiable']],
        );
    }

    public function testProcessNodeSkipsAnAnonymousClass(): void
    {
        self::assertNull((new ClassAncestorCollector(self::createReflectionProvider()))->processNode(
            new \PhpParser\Node\Stmt\Class_(null),
            self::createStub(Scope::class),
        ));
    }

    public function testProcessNodeSkipsADeclarationReflectionDoesNotKnow(): void
    {
        $node = new \PhpParser\Node\Stmt\Class_('Absent');
        $node->namespacedName = new \PhpParser\Node\Name\FullyQualified('Tests\\Fixture\\RequireExhaustiveDispatch\\Absent');

        self::assertNull((new ClassAncestorCollector(self::createReflectionProvider()))->processNode($node, self::createStub(Scope::class)));
    }
}
