<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Mutation;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Mutation\CallableId;
use Toolkit\PhpStan\Rule\Mutation\MutationDeclarationCollector;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;

/**
 * @covers \Toolkit\PhpStan\Rule\Mutation\MutationDeclarationCollector
 */
#[CoversClass(MutationDeclarationCollector::class)]
#[UsesClass(CallableId::class)]
#[UsesClass(RulePhpDocParser::class)]
final class MutationDeclarationCollectorTest extends TestCase
{
    public function testGetNodeTypeReturnsAnyParserNode(): void
    {
        self::assertSame(Node::class, (new MutationDeclarationCollector())->getNodeType());
    }

    public function testProcessNodeCollectsCompactContract(): void
    {
        $parameter = new Param(new Variable('value'));
        $function = new Function_('change', ['params' => [$parameter]], [
            'comments' => [new Doc('/** @param object $value +mut changed */')],
        ]);
        $function->namespacedName = new Name('App\change');
        $result = (new MutationDeclarationCollector())->processNode($function, self::createStub(Scope::class));

        self::assertSame(['value'], $result['mutable'] ?? null);
        self::assertSame('App\change()', $result['symbol']);
    }

    public function testIdentityNamesAFunction(): void
    {
        $function = new Function_('run');
        $function->namespacedName = new Name('App\run');

        self::assertSame('function:app\run', (new MutationDeclarationCollector())->identity($function, self::createStub(Scope::class))['key'] ?? null);
    }

    public function testPrototypesReturnsNoneWithoutAClassScope(): void
    {
        self::assertSame([], (new MutationDeclarationCollector())->prototypes(
            new ClassMethod('run'),
            self::createStub(Scope::class),
        ));
    }
}
