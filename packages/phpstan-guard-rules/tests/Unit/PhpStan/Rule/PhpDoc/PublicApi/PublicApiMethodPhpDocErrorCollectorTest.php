<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc\PublicApi;

use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiMethodPhpDocErrorCollector;

/**
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiMethodPhpDocErrorCollector
 */
#[CoversClass(PublicApiMethodPhpDocErrorCollector::class)]
final class PublicApiMethodPhpDocErrorCollectorTest extends TestCase
{
    public function testErrorsReturnsMethodPhpDocError(): void
    {
        $class = new Class_('Example', [
            'stmts' => [
                new \PhpParser\Node\Stmt\ClassMethod('run', ['flags' => Class_::MODIFIER_PUBLIC]),
            ],
        ]);

        $errors = (new PublicApiMethodPhpDocErrorCollector())->errors($class, 'Example');

        self::assertSame('customRules.requirePhpDocOnMethod', $errors[0]->getIdentifier());
    }
}
