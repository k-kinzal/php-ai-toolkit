<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestAssertion\NoRedundantAssertInstanceOfErrorBuilder;

/**
 * @covers \Toolkit\PhpStan\Rule\TestAssertion\NoRedundantAssertInstanceOfErrorBuilder
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
