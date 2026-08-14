<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\RequirePhpDocOnPublicApi;

use PhpAiToolkit\PhpStan\Rule\RequirePhpDocOnPublicApi\PublicApiClassPhpDocErrorCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
