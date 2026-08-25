<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc\PublicApi;

use PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiClassPhpDocErrorCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiClassPhpDocErrorCollector
 */
#[CoversClass(PublicApiClassPhpDocErrorCollector::class)]
final class PublicApiClassPhpDocErrorCollectorTest extends TestCase
{
    public function testErrorsReturnsClassPhpDocError(): void
    {
        $errors = (new PublicApiClassPhpDocErrorCollector())->errors(
            new \PhpParser\Node\Stmt\Class_('Example'),
            'Class',
            'Example',
        );

        self::assertSame('customRules.requirePhpDocOnClass', $errors[0]->getIdentifier());
    }
}
