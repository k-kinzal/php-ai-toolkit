<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PhpAiToolkit\PhpStan\Rule\PhpDoc\MissingExampleErrorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MissingExampleErrorBuilder::class)]
final class MissingExampleErrorBuilderTest extends TestCase
{
    public function testBuildNamesTheSubjectAndCarriesIdentifierAndLine(): void
    {
        $error = (new MissingExampleErrorBuilder())->build('customRules.requireExampleOnClass', 'class Ledger', 12);

        self::assertSame('customRules.requireExampleOnClass', $error->getIdentifier());
        self::assertStringContainsString('Add an @example block to class Ledger', $error->getMessage());
        self::assertStringContainsString('"@visibility public"', $error->getMessage());
        self::assertStringContainsString('// => value', $error->getMessage());
    }
}
