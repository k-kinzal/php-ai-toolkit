<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

use PhpAiToolkit\PhpStan\Rule\TestAssertion\NoRedundantAssertInstanceOfErrorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\TestAssertion\NoRedundantAssertInstanceOfErrorBuilder
 */
#[CoversClass(NoRedundantAssertInstanceOfErrorBuilder::class)]
final class NoRedundantAssertInstanceOfErrorBuilderTest extends TestCase
{
    public function testBuildReturnsRedundantAssertInstanceOfError(): void
    {
        $error = (new NoRedundantAssertInstanceOfErrorBuilder())->build('App\\Service', 'App\\ServiceInterface', 12);

        self::assertSame('customRules.noRedundantAssertInstanceOf', $error->getIdentifier());
    }
}
