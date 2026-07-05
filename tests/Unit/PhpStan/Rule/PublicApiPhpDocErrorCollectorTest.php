<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\PublicApiClassPhpDocErrorCollector;
use PhpAiToolkit\PhpStan\Rule\PublicApiConstantPhpDocErrorCollector;
use PhpAiToolkit\PhpStan\Rule\PublicApiMethodPhpDocErrorCollector;
use PhpAiToolkit\PhpStan\Rule\PublicApiPhpDocErrorCollector;
use PhpAiToolkit\PhpStan\Rule\PublicApiPropertyPhpDocErrorCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PublicApiPhpDocErrorCollector::class)]
#[UsesClass(PublicApiClassPhpDocErrorCollector::class)]
#[UsesClass(PublicApiConstantPhpDocErrorCollector::class)]
#[UsesClass(PublicApiMethodPhpDocErrorCollector::class)]
#[UsesClass(PublicApiPropertyPhpDocErrorCollector::class)]
final class PublicApiPhpDocErrorCollectorTest extends TestCase
{
    public function testErrorsReturnsMergedPublicApiErrors(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Example', [
            'stmts' => [
                new \PhpParser\Node\Stmt\ClassMethod('run', ['flags' => \PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC]),
                new \PhpParser\Node\Stmt\Property(\PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC, [new \PhpParser\Node\PropertyItem('name')]),
                new \PhpParser\Node\Stmt\ClassConst(
                    [new \PhpParser\Node\Const_('STATUS', new \PhpParser\Node\Scalar\Int_(1))],
                    \PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC,
                ),
            ],
        ]);

        $errors = (new PublicApiPhpDocErrorCollector())->errors($class, 'Class', 'Example');

        self::assertCount(4, $errors);
    }
}
