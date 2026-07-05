<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\PublicMethodTestCoverageErrorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PublicMethodTestCoverageErrorBuilder::class)]
final class PublicMethodTestCoverageErrorBuilderTest extends TestCase
{
    public function testBuildReturnsPublicMethodWithoutTestError(): void
    {
        $error = (new PublicMethodTestCoverageErrorBuilder())->build('getResult', 'testGetResult', 12);

        self::assertSame('customRules.publicMethodWithoutTest', $error->getIdentifier());
    }
}
