<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type;

use PhpAiToolkit\PhpStan\Rule\Type\MixedTypeErrorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MixedTypeErrorBuilder::class)]
final class MixedTypeErrorBuilderTest extends TestCase
{
    public function testBuildIdentifiesTheDeclarationAndFix(): void
    {
        $error = (new MixedTypeErrorBuilder())->build('array<mixed>', 'return type', 'Reader::read()', 17);

        self::assertSame('customRules.internalMixedType', $error->getIdentifier());
        self::assertStringContainsString('array<mixed>', $error->getMessage());
        self::assertStringContainsString('Reader::read()', $error->getMessage());
        self::assertStringContainsString('Validate arbitrary input', $error->getMessage());
    }
}
