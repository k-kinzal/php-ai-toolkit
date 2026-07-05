<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\NoRedundantAssertInstanceOfErrorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NoRedundantAssertInstanceOfErrorBuilder::class)]
final class NoRedundantAssertInstanceOfErrorBuilderTest extends TestCase
{
    public function testBuildReturnsRedundantAssertInstanceOfError(): void
    {
        $error = (new NoRedundantAssertInstanceOfErrorBuilder())->build('App\\Service', 'App\\ServiceInterface', 12);

        self::assertSame('customRules.noRedundantAssertInstanceOf', $error->getIdentifier());
    }
}
