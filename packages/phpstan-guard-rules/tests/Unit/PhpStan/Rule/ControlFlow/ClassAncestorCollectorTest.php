<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Tests\PhpStan\RequireExhaustiveDispatch\MasterCard;
use Tests\PhpStan\RequireExhaustiveDispatch\Payment;
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
            ClassLike::class,
            (new ClassAncestorCollector(self::createReflectionProvider()))->getNodeType(),
        );
    }

    public function testProcessNodeNamesAnInstantiableClass(): void
    {
        $node = new Class_('MasterCard');
        $node->namespacedName = new FullyQualified(MasterCard::class);
        $collected = (new ClassAncestorCollector(self::createReflectionProvider()))->processNode($node, self::createStub(Scope::class));

        self::assertSame(
            [MasterCard::class, true],
            [$collected === null ? null : $collected['name'], $collected === null ? null : $collected['instantiable']],
        );
    }

    public function testProcessNodeRecordsEveryAncestorOfAClass(): void
    {
        $node = new Class_('MasterCard');
        $node->namespacedName = new FullyQualified(MasterCard::class);
        $collected = (new ClassAncestorCollector(self::createReflectionProvider()))->processNode($node, self::createStub(Scope::class));

        self::assertContains(Payment::class, $collected === null ? [] : $collected['ancestors']);
    }

    public function testProcessNodeMarksAnInterfaceAsNotInstantiable(): void
    {
        $node = new Interface_('Payment');
        $node->namespacedName = new FullyQualified(Payment::class);
        $collected = (new ClassAncestorCollector(self::createReflectionProvider()))->processNode($node, self::createStub(Scope::class));

        self::assertSame(
            [Payment::class, false],
            [$collected === null ? null : $collected['name'], $collected === null ? null : $collected['instantiable']],
        );
    }

    public function testProcessNodeSkipsAnAnonymousClass(): void
    {
        self::assertNull((new ClassAncestorCollector(self::createReflectionProvider()))->processNode(
            new Class_(null),
            self::createStub(Scope::class),
        ));
    }

    public function testProcessNodeSkipsADeclarationReflectionDoesNotKnow(): void
    {
        $node = new Class_('Absent');
        $node->namespacedName = new FullyQualified('Tests\\PhpStan\\RequireExhaustiveDispatch\\Absent');

        self::assertNull((new ClassAncestorCollector(self::createReflectionProvider()))->processNode($node, self::createStub(Scope::class)));
    }
}
