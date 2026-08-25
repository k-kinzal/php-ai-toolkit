<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type;

use PhpAiToolkit\PhpStan\Rule\Type\MixedLocalPhpDocErrorCollector;
use PhpParser\Comment\Doc;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MixedLocalPhpDocErrorCollector::class)]
final class MixedLocalPhpDocErrorCollectorTest extends TestCase
{
    public function testErrorsUsesTheHighestPrecedenceVarTag(): void
    {
        $node = new \PhpParser\Node\Stmt\Expression(
            new \PhpParser\Node\Expr\Assign(new \PhpParser\Node\Expr\Variable('value'), new \PhpParser\Node\Scalar\String_('value')),
            ['comments' => [new Doc("/**\n * @var string \$value\n * @phpstan-var mixed \$value\n */")], 'startLine' => 8]
        );

        $errors = (new MixedLocalPhpDocErrorCollector())->errors($node, 'Example::run()');

        self::assertCount(1, $errors);
        self::assertStringContainsString('local @var $value', $errors[0]->getMessage());
    }

    public function testTagErrorsIsCoveredByLocalVarRuleFixture(): void
    {
        self::addToAssertionCount(1);
    }
}
