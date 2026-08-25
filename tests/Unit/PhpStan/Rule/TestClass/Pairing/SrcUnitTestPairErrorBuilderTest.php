<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass\Pairing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder
 */
#[CoversClass(SrcUnitTestPairErrorBuilder::class)]
final class SrcUnitTestPairErrorBuilderTest extends TestCase
{
    public function testMissingUnitTestReturnsSourceWithoutUnitTestError(): void
    {
        $error = (new SrcUnitTestPairErrorBuilder())->missingUnitTest('/src/', '/tests/Unit/', 'User.php', 'UserTest.php');

        self::assertSame('customRules.srcWithoutUnitTest', $error->getIdentifier());
    }

    public function testMissingSourceReturnsUnitTestWithoutSourceError(): void
    {
        $error = (new SrcUnitTestPairErrorBuilder())->missingSource('/src/', '/tests/Unit/', 'UserTest.php', 'User.php');

        self::assertSame('customRules.unitTestWithoutSource', $error->getIdentifier());
    }
}
