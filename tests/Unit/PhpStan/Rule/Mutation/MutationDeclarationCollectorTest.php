<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Mutation;

use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\Mutation\MutationContract;
use Toolkit\Mutation\MutationContractReader;
use Toolkit\PhpStan\Rule\Mutation\CallableId;
use Toolkit\PhpStan\Rule\Mutation\MutationDeclarationCollector;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;

/**
 * @covers \Toolkit\PhpStan\Rule\Mutation\MutationDeclarationCollector
 */
#[CoversClass(MutationDeclarationCollector::class)]
#[UsesClass(CallableId::class)]
#[UsesClass(MutationContract::class)]
#[UsesClass(MutationContractReader::class)]
#[UsesClass(RulePhpDocParser::class)]
final class MutationDeclarationCollectorTest extends TestCase
{
    public function testGetNodeTypeReturnsAnyParserNode(): void
    {
        self::assertSame(\PhpParser\Node::class, (new MutationDeclarationCollector())->getNodeType());
    }

    public function testProcessNodeCollectsCompactContract(): void
    {
        $parameter = new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('value'));
        $function = new \PhpParser\Node\Stmt\Function_('change', ['params' => [$parameter]], [
            'comments' => [new \PhpParser\Comment\Doc('/** @param object $value +mut changed */')],
        ]);
        $function->namespacedName = new \PhpParser\Node\Name('App\change');
        $result = (new MutationDeclarationCollector())->processNode($function, self::createStub(Scope::class));

        self::assertSame(['value'], $result['mutable'] ?? null);
        self::assertSame('App\change()', $result['symbol']);
    }

    public function testIdentityNamesAFunction(): void
    {
        $function = new \PhpParser\Node\Stmt\Function_('run');
        $function->namespacedName = new \PhpParser\Node\Name('App\run');

        self::assertSame('function:app\run', (new MutationDeclarationCollector())->identity($function, self::createStub(Scope::class))['key'] ?? null);
    }

    public function testPrototypesReturnsNoneWithoutAClassScope(): void
    {
        self::assertSame([], (new MutationDeclarationCollector())->prototypes(
            new \PhpParser\Node\Stmt\ClassMethod('run'),
            self::createStub(Scope::class),
        ));
    }
}
