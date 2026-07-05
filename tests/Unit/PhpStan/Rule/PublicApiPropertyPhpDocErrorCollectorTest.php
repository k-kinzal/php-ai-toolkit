<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\PublicApiPropertyPhpDocErrorCollector;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PublicApiPropertyPhpDocErrorCollector::class)]
final class PublicApiPropertyPhpDocErrorCollectorTest extends TestCase
{
    public function testErrorsReturnsPropertyPhpDocError(): void
    {
        $class = new Class_('Example', [
            'stmts' => [
                new \PhpParser\Node\Stmt\Property(
                    Class_::MODIFIER_PUBLIC,
                    [new \PhpParser\Node\PropertyItem('name')],
                ),
            ],
        ]);

        $errors = (new PublicApiPropertyPhpDocErrorCollector())->errors($class, 'Example');

        self::assertSame('customRules.requirePhpDocOnProperty', $errors[0]->getIdentifier());
    }
}
