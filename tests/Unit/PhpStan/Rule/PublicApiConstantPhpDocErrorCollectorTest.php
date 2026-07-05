<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\PublicApiConstantPhpDocErrorCollector;
use PhpParser\Modifiers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PublicApiConstantPhpDocErrorCollector::class)]
final class PublicApiConstantPhpDocErrorCollectorTest extends TestCase
{
    public function testErrorsReturnsConstantPhpDocError(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Example', [
            'stmts' => [
                new \PhpParser\Node\Stmt\ClassConst(
                    [new \PhpParser\Node\Const_('STATUS', new \PhpParser\Node\Scalar\Int_(1))],
                    Modifiers::PUBLIC,
                ),
            ],
        ]);

        $errors = (new PublicApiConstantPhpDocErrorCollector())->errors($class, 'Example');

        self::assertSame('customRules.requirePhpDocOnConstant', $errors[0]->getIdentifier());
    }
}
