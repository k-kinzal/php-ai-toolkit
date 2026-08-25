<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestClass\PublicMethodTestCoverageErrorBuilder;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\PublicMethodTestCoverageErrorBuilder
 */
#[CoversClass(PublicMethodTestCoverageErrorBuilder::class)]
final class PublicMethodTestCoverageErrorBuilderTest extends TestCase
{
    public function testBuildReturnsPublicMethodWithoutTestError(): void
    {
        $error = (new PublicMethodTestCoverageErrorBuilder())->build('getResult', 'testGetResult', 12);

        self::assertSame('customRules.publicMethodWithoutTest', $error->getIdentifier());
    }
}
